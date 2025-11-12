# Meilisearch Setup Guide

## Quick Start - One Command to Sync Everything

After loading a database, sync all data to Meilisearch with one simple command:

```bash
./yii meilisearch/sync
```

This single command will:
1. ✅ Initialize Meilisearch indexes (creates them if they don't exist)
2. ✅ Sync all candidates to Meilisearch
3. ✅ Sync all fulltimers to Meilisearch
4. ✅ Show progress and summary

**That's it!** No need to remember entity names, multiple commands, or complex arguments.

## Available Commands

### Sync Everything
```bash
./yii meilisearch/sync
```
Initializes indexes and syncs all candidates and fulltimers.

### Initialize Indexes Only
```bash
./yii meilisearch/init
```
Creates and configures Meilisearch indexes without syncing data.

## Prerequisites

1. **Meilisearch service running**: 
   ```bash
   docker-compose up -d meilisearch
   ```

2. **Environment configured**: 
   - Copy `.env.template` to `.env`
   - Set `MEILI_MASTER_KEY` in `.env` (or use default for dev)

3. **Database loaded**: Your database should have candidate and fulltimer data

## Troubleshooting

### "Meilisearch is not configured"
- Check that `MEILI_MASTER_KEY` is set in `.env` or `params-local.php`
- Verify Meilisearch service is running: `docker-compose ps meilisearch`

### "Connection refused" or database errors
- Wait for MySQL to be ready: `docker-compose ps mysql`
- Ensure database is loaded and accessible

### Sync takes a long time
- This is normal for large datasets
- Progress is shown during sync
- Syncs in batches of 100 records

## What Gets Synced

- **Candidates**: All non-deleted candidates with active job search status
- **Fulltimers**: All fulltimers in the database
- **Majors**: All majors (if `meilisearch_major_index` is configured in params)

Data is automatically kept in sync when records are created/updated/deleted through the application.

## Optional: Major Indexing

Majors (university majors) can be indexed for search. To enable:

1. Add to your environment config (e.g., `environments/dev/common/config/params-local.php`):
   ```php
   'meilisearch_major_index' => 'dev_major_public',
   ```

2. Run sync: `./yii meilisearch/sync`

The sync command will automatically include majors if the index is configured.

