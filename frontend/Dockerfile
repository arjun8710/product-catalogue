# Build the static bundle, then serve it with nginx, which also proxies /api to the backend.
FROM node:24-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

# Optional: run lint and tests without Node installed locally:
#   docker build --target test ./frontend
FROM build AS test
RUN npm run lint && npm test

FROM nginx:1.30-alpine AS serve
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
