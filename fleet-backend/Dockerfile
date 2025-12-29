FROM python:3.11-slim

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements first (for better caching)
COPY requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code
COPY . .

# Create non-root user
RUN useradd -m -u 1000 fleet && chown -R fleet:fleet /app
USER fleet

# Expose port
EXPOSE 5001

# Default command (can be overridden in docker-compose)
CMD ["gunicorn", "-b", "0.0.0.0:5001", "-w", "4", "--timeout", "120", "app:app"]
