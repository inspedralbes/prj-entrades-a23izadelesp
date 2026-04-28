const Redis = require('ioredis');

const REDIS_URL = process.env.REDIS_URL || 'redis://localhost:6379';
const REDIS_PREFIX = process.env.REDIS_PREFIX || '';
const LEGACY_PREFIX = 'queuely-database-';

class RedisSubscriber {
  constructor(io) {
    this.io = io;
    this.subscriber = new Redis(REDIS_URL);
    this.setupListeners();
  }

  setupListeners() {
    const baseChannels = [
      'seat:locked',
      'seat:released',
      'zone:locked',
      'zone:released',
      'zone-seat:locked',
      'zone-seat:released',
      'queue:updated',
      'booking:processing',
      'booking:confirmed',
      'booking:failed'
    ];

    const prefixedChannels = REDIS_PREFIX
      ? baseChannels.map(channel => `${REDIS_PREFIX}${channel}`)
      : [];

    const legacyChannels = baseChannels.map(channel => `${LEGACY_PREFIX}${channel}`);

    const channels = [...new Set([...baseChannels, ...prefixedChannels, ...legacyChannels])];

    channels.forEach(channel => {
      this.subscriber.subscribe(channel, (err) => {
        if (err) {
          console.error(`Error subscribing to ${channel}:`, err);
        } else {
          console.log(`Subscribed to ${channel}`);
        }
      });
    });

    this.subscriber.on('message', (channel, message) => {
      this.handleMessage(channel, message);
    });
  }

  handleMessage(channel, message) {
    try {
      const data = JSON.parse(message);
      console.log(`Received from ${channel}:`, data);

      const normalizedChannel = channel
        .replace(LEGACY_PREFIX, '')
        .replace(REDIS_PREFIX, '');

      switch (normalizedChannel) {
        case 'seat:locked':
        case 'seat:released':
        case 'zone:locked':
        case 'zone:released':
        case 'zone-seat:locked':
        case 'zone-seat:released':
          if (data.session_id) {
            this.io.to(`session:${data.session_id}`).emit(normalizedChannel, data);
          }
          break;

        case 'queue:updated':
          if (data.session_id) {
            if (data.status === 'admitted' || data.admitted === true || data.position === 0) {
              this.io.to(`session:${data.session_id}`).emit('queue:admitted', {
                session_id: data.session_id,
                identifier: data.identifier,
              });
            }

            this.io.to(`session:${data.session_id}`).emit('queue:position', data);

            if (typeof data.count === 'number') {
              this.io.to(`session:${data.session_id}`).emit('queue:remaining', {
                session_id: data.session_id,
                count: data.count,
              });
            }
          }
          break;

        case 'booking:processing':
        case 'booking:confirmed':
        case 'booking:failed':
          if (data.socket_id) {
            this.io.to(data.socket_id).emit(normalizedChannel, data);
          }
          break;
      }
    } catch (err) {
      console.error('Error parsing message:', err);
    }
  }

  close() {
    this.subscriber.quit();
  }
}

module.exports = RedisSubscriber;