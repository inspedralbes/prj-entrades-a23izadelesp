FROM node:20-alpine

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

EXPOSE 3000

ENV HOST=0.0.0.0
ENV PORT=3000
ENV API_BASE_URL=/api
ENV SOCKET_URL=http://localhost:3001

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]