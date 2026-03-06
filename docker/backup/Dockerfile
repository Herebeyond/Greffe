# Backup service Dockerfile
# Uses PostgreSQL client tools for backup operations

FROM postgres:16-alpine

# Install additional tools
RUN apk add --no-cache \
    bash \
    rsync \
    tzdata

# Create scripts directory
RUN mkdir -p /scripts

# Copy backup scripts
COPY full-backup.sh /scripts/
COPY incremental-backup.sh /scripts/
COPY restore.sh /scripts/
COPY scheduler.sh /scripts/

# Make scripts executable
RUN chmod +x /scripts/*.sh

# Create backup directory
RUN mkdir -p /backups

# Set working directory
WORKDIR /scripts

# Run the scheduler by default
CMD ["/scripts/scheduler.sh"]
