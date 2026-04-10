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
      'seat:locked',
      'queuely-database-seat:released',
      'seat:released',
      'queuely-database-zone:locked',
      'zone:locked',
      'queuely-database-zone:released',
      'zone:released',
      'queuely-database-zone-seat:locked',
      'zone-seat:locked',
      'queuely-database-zone-seat:released',
      'zone-seat:released',
      'queuely-database-queue:updated',
      'queue:updated',
      'queuely-database-booking:processing',
      'booking:processing',
      'queuely-database-booking:confirmed',
      'booking:confirmed',
      'queuely-database-booking:failed',
      'booking:failed'
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

      const normalizedChannel = channel.replace('queuely-database-', '');

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