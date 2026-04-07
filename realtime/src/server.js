const { createServer } = require('http');
const { Server } = require('socket.io');
const Redis = require('ioredis');
const RedisSubscriber = require('./redisSubscriber');
const QueueManager = require('./queueManager');

const PORT = process.env.SOCKET_PORT || 3001;
const REDIS_URL = process.env.REDIS_URL || 'redis://localhost:6379';

const httpServer = createServer();
const io = new Server(httpServer, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST']
  }
});

const redis = new Redis(REDIS_URL);
const redisSubscriber = new RedisSubscriber(io);
const queueManager = new QueueManager(io, redis);

io.on('connection', (socket) => {
  console.log(`Client connected: ${socket.id}`);

  socket.on('join:session', async (sessionId) => {
    socket.join(`session:${sessionId}`);
    console.log(`Socket ${socket.id} joined session:${sessionId}`);
    
    await redis.sadd('active_sessions', sessionId);
  });

  socket.on('join:queue', async (data) => {
    const { session_id, user_id } = data;
    await queueManager.addToQueue(session_id, socket.id, user_id);
    socket.join(`session:${session_id}`);
  });

  socket.on('leave:session', async (sessionId) => {
    socket.leave(`session:${sessionId}`);
    await queueManager.removeFromQueue(sessionId, socket.id);
  });

  socket.on('disconnect', async () => {
    console.log(`Client disconnected: ${socket.id}`);
  });
});

httpServer.listen(PORT, () => {
  console.log(`Socket.IO server running on port ${PORT}`);
});

process.on('SIGTERM', () => {
  redisSubscriber.close();
  redis.quit();
  httpServer.close();
});