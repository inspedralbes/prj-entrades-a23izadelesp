const { createServer } = require('http');
const { Server } = require('socket.io');

const PORT = process.env.SOCKET_PORT || 3001;

const httpServer = createServer();
const io = new Server(httpServer, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST']
  }
});

io.on('connection', (socket) => {
  console.log(`Client connected: ${socket.id}`);

  socket.on('join:session', (sessionId) => {
    socket.join(`session:${sessionId}`);
    console.log(`Socket ${socket.id} joined session:${sessionId}`);
  });

  socket.on('disconnect', () => {
    console.log(`Client disconnected: ${socket.id}`);
  });
});

httpServer.listen(PORT, () => {
  console.log(`Socket.IO server running on port ${PORT}`);
});
