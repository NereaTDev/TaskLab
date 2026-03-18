const { Client, GatewayIntentBits } = require('discord.js');

// ─── Config ──────────────────────────────────────────────────────────────────

const TASKLAB_URL   = (process.env.TASKLAB_URL || '').replace(/\/$/, '');
const TEAMS_TOKEN   = process.env.SERVICES_TEAMS_TOKEN;
const BOT_TOKEN     = process.env.DISCORD_BOT_TOKEN;

// Optional: comma-separated list of channel IDs to monitor.
// If empty, ALL channels in ALL guilds the bot is in are monitored.
const ALLOWED_CHANNELS = (process.env.DISCORD_CHANNEL_IDS || '')
    .split(',')
    .map(s => s.trim())
    .filter(Boolean);

if (!TASKLAB_URL || !TEAMS_TOKEN || !BOT_TOKEN) {
    console.error('[bot] Missing required env vars: TASKLAB_URL, SERVICES_TEAMS_TOKEN, DISCORD_BOT_TOKEN');
    process.exit(1);
}

console.log('[bot] Starting TaskLab Discord bot');
console.log('[bot] Forwarding to:', TASKLAB_URL);
console.log('[bot] Channel filter:', ALLOWED_CHANNELS.length ? ALLOWED_CHANNELS.join(', ') : '(all channels)');

// ─── Discord client ───────────────────────────────────────────────────────────

const client = new Client({
    intents: [
        GatewayIntentBits.Guilds,
        GatewayIntentBits.GuildMessages,
        GatewayIntentBits.MessageContent,  // required to read message.content
    ],
});

client.once('ready', () => {
    console.log(`[bot] Logged in as ${client.user.tag}`);
});

client.on('messageCreate', async (message) => {
    // Skip bots (including self)
    if (message.author.bot) return;

    // Apply channel filter if configured
    if (ALLOWED_CHANNELS.length > 0 && !ALLOWED_CHANNELS.includes(message.channelId)) return;

    // Build the native Discord payload (matches what the controller already handles)
    const payload = {
        id:         message.id,
        content:    message.content,
        channel_id: message.channelId,
        guild_id:   message.guildId,
        jump_url:   `https://discord.com/channels/${message.guildId}/${message.channelId}/${message.id}`,
        author: {
            id:            message.author.id,
            username:      message.author.username,
            discriminator: message.author.discriminator,
        },
        attachments: message.attachments.map(att => ({
            id:           att.id,
            url:          att.url,
            filename:     att.name,
            content_type: att.contentType ?? '',
            size:         att.size,
        })),
        embeds: message.embeds.map(e => ({
            type:        e.data.type,
            title:       e.title,
            description: e.description,
            url:         e.url,
            image:       e.image  ? { url: e.image.url }     : undefined,
            thumbnail:   e.thumbnail ? { url: e.thumbnail.url } : undefined,
        })),
    };

    console.log(`[bot] Message ${message.id} from ${message.author.username} in #${message.channelId} — forwarding…`);

    try {
        const res = await fetch(`${TASKLAB_URL}/integrations/discord/messages`, {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-Teams-Token': TEAMS_TOKEN,
            },
            body: JSON.stringify(payload),
        });

        const body = await res.text();
        if (res.ok) {
            console.log(`[bot] Forwarded OK (${res.status}): ${body}`);
        } else {
            console.error(`[bot] TaskLab returned ${res.status}: ${body}`);
        }
    } catch (err) {
        console.error('[bot] Failed to forward message:', err.message);
    }
});

client.on('error', (err) => {
    console.error('[bot] Discord client error:', err.message);
});

client.login(BOT_TOKEN).catch(err => {
    console.error('[bot] Login failed:', err.message);
    process.exit(1);
});
