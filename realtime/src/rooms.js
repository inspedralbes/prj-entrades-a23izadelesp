class RoomManager {
  constructor(io) {
    this.io = io;
  }

  joinSession(socket, sessionId) {
    const room = `session:${sessionId}`;
    socket.join(room);
    console.log(`Socket ${socket.id} joined ${room}`);
  }

  leaveSession(socket, sessionId) {
    const room = `session:${sessionId}`;
    socket.leave(room);
    console.log(`Socket ${socket.id} left ${room}`);
  }

  getSessionRoom(sessionId) {
    return `session:${sessionId}`;
  }

  broadcastToSession(sessionId, event, data) {
    this.io.to(`session:${sessionId}`).emit(event, data);
  }

  emitToSocket(socketId, event, data) {
    this.io.to(socketId).emit(event, data);
  }
}

module.exports = RoomManager;