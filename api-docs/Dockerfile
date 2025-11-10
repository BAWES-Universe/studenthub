FROM nginx:alpine

# Copy custom nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy all HTML files
COPY *.html /usr/share/nginx/html/

# Expose port 80
EXPOSE 80

