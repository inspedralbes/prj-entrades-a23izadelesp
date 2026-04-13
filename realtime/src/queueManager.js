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
          const usersToAdmit = await this.redis.lrange(queueKey, 0, this.admissionBatchSize - 1);
          console.log(`Admitiendo a ${usersToAdmit.length} usuarios para sesión ${sessionId}`, usersToAdmit);
          
          for (const identifier of usersToAdmit) {
            console.log(`Emitiendo queue:admitted a session:${sessionId} para identifier:${identifier}`);
            this.io.to(`session:${sessionId}`).emit('queue:admitted', {
              session_id: sessionId,
              identifier: identifier
            });
            this.io.to(`session:${sessionId}`).emit('queue:position', {
              session_id: sessionId,
              identifier: identifier,
              position: 0,
              admitted: true
            });
            await this.redis.sadd(`queue:${sessionId}:active`, identifier);
            await this.redis.del(`queue:position:${sessionId}:${identifier}`);
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

  async removeByIdentifier(sessionId, identifier) {
    const queueKey = `queue:${sessionId}`;
    const removedCount = await this.redis.lrem(queueKey, 0, identifier);
    await this.redis.srem(`queue:${sessionId}:active`, identifier);
    await this.redis.del(`queue:position:${sessionId}:${identifier}`);
    
    if (removedCount > 0) {
      // Notificar a los que quedan del cambio de posición, lo hará Laravel o el bucle
      const remaining = await this.redis.lrange(queueKey, 0, -1);
      for (let i = 0; i < remaining.length; i++) {
        const id = remaining[i];
        const pos = i + 1;
        await this.redis.set(`queue:position:${sessionId}:${id}`, pos);
        this.io.to(`session:${sessionId}`).emit('queue:position', {
          session_id: sessionId,
          identifier: id,
          position: pos
        });
      }
      
      this.io.to(`session:${sessionId}`).emit('queue:remaining', {
        session_id: sessionId,
        count: remaining.length
      });
    }
  }
}

module.exports = QueueManager;