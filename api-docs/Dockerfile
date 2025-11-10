FROM nginx:alpine

# Copy custom nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy HTML file with Scalar embedded
COPY index.html /usr/share/nginx/html/index.html

# Expose port 80
EXPOSE 80

