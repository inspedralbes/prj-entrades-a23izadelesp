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

const redis = new Redis(REDIS_URL, { keyPrefix: 'queuely-database-' });
const redisSubscriber = new RedisSubscriber(io);
const queueManager = new QueueManager(io, redis);

io.on('connection', (socket) => {
  console.log(`Client connected: ${socket.id}`);

  socket.on('join:session', async (sessionId) => {
    socket.join(`session:${sessionId}`);
    console.log(`Socket ${socket.id} joined session:${sessionId}`);
    
    await redis.sadd('active_sessions', sessionId);
  });

  socket.on('register:queue', async (data) => {
    const { session_id, identifier } = data;
    socket.data = { session_id, identifier };
    socket.join(`session:${session_id}`);
    await redis.sadd('active_sessions', session_id);
    console.log(`Socket ${socket.id} registered for queue ${session_id} as ${identifier}`);
  });

  socket.on('disconnect', async () => {
    console.log(`Client disconnected: ${socket.id}`);
    if (socket.data && socket.data.session_id && socket.data.identifier) {
      await queueManager.removeByIdentifier(socket.data.session_id, socket.data.identifier);
      console.log(`Removed ${socket.data.identifier} from queue ${socket.data.session_id} due to disconnect`);
    }
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