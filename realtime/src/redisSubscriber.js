const Redis = require('ioredis');

const REDIS_URL = process.env.REDIS_URL || 'redis://localhost:6379';

class RedisSubscriber {
  constructor(io) {
    this.io = io;
    this.subscriber = new Redis(REDIS_URL);
    this.setupListeners();
  }

  setupListeners() {
    const channels = [
      'queuely-database-seat:locked',
      'queuely-database-seat:released',
      'queuely-database-queue:updated',
      'queuely-database-booking:processing',
      'queuely-database-booking:confirmed',
      'queuely-database-booking:failed'
    ];

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

      switch (channel) {
        case 'queuely-database-seat:locked':
        case 'queuely-database-seat:released':
          if (data.session_id) {
            this.io.to(`session:${data.session_id}`).emit(channel.replace('queuely-database-', ''), data);
          }
          break;

        case 'queuely-database-queue:updated':
          if (data.session_id && data.socket_id) {
            this.io.to(`session:${data.session_id}`).emit('queue:position', data);
          }
          break;

        case 'queuely-database-booking:processing':
        case 'queuely-database-booking:confirmed':
        case 'queuely-database-booking:failed':
          if (data.socket_id) {
            this.io.to(data.socket_id).emit(channel.replace('queuely-database-', ''), data);
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