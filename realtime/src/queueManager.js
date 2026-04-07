class QueueManager {
  constructor(io, redis) {
    this.io = io;
    this.redis = redis;
    this.admissionBatchSize = parseInt(process.env.ADMISSION_BATCH_SIZE) || 10;
    this.admissionInterval = parseInt(process.env.ADMISSION_INTERVAL) || 5000;
    this.startAdmissionLoop();
  }

  async startAdmissionLoop() {
    setInterval(async () => {
      await this.processAdmission();
    }, this.admissionInterval);
  }

  async processAdmission() {
    try {
      const sessions = await this.redis.smembers('active_sessions');
      
      for (const sessionId of sessions) {
        const queueKey = `queue:${sessionId}`;
        const position = await this.redis.lindex(queueKey, 0);
        
        if (position) {
          const userData = JSON.parse(position);
          const usersToAdmit = await this.redis.lrange(queueKey, 0, this.admissionBatchSize - 1);
          
          for (const user of usersToAdmit) {
            const user = JSON.parse(user);
            if (user.socket_id) {
              this.io.to(user.socket_id).emit('queue:admitted', {
                session_id: sessionId
              });
              this.io.to(`session:${sessionId}`).emit('queue:position', {
                session_id: sessionId,
                position: 0,
                admitted: true
              });
            }
          }
          
          await this.redis.ltrim(queueKey, this.admissionBatchSize, -1);
          
          const remaining = await this.redis.llen(queueKey);
          this.io.to(`session:${sessionId}`).emit('queue:remaining', {
            session_id: sessionId,
            count: remaining
          });
        }
      }
    } catch (err) {
      console.error('Error processing admission:', err);
    }
  }

  async addToQueue(sessionId, socketId, userId) {
    const queueKey = `queue:${sessionId}`;
    const userData = JSON.stringify({ socket_id: socketId, user_id: userId });
    
    await this.redis.rpush(queueKey, userData);
    
    const position = await this.redis.llen(queueKey);
    
    this.io.to(socketId).emit('queue:position', {
      session_id: sessionId,
      position: position,
      message: 'Gestionando su solicitud...'
    });
  }

  async removeFromQueue(sessionId, socketId) {
    const queueKey = `queue:${sessionId}`;
    const users = await this.redis.lrange(queueKey, 0, -1);
    
    for (let i = 0; i < users.length; i++) {
      const user = JSON.parse(users[i]);
      if (user.socket_id === socketId) {
        await this.redis.lset(queueKey, i, '__DELETED__');
        await this.redis.lrem(queueKey, 0, '__DELETED__');
        break;
      }
    }
  }
}

module.exports = QueueManager;