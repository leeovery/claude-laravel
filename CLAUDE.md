# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Claude Laravel Plugin - provides Claude Code skills for Laravel development guidance. Skills are markdown-based documentation files that get symlinked to `.claude/skills/` via the `leeovery/claude-manager` package.

## Project Structure

```
skills/                    # All Laravel skills (each has SKILL.md + references/)
  laravel-{topic}/
    SKILL.md              # Main skill entry point with description
    references/           # Detailed documentation files
release                   # Bash script for semver releases with AI-generated notes
release.txt               # Current version number
```

## Commands

### Release

```bash
./release              # Patch release (default)
./release -m           # Minor release
./release -M           # Major release
./release -d           # Dry run (preview)
./release --no-ai      # Skip AI commit message generation
```

## Development Notes

- No PHP source code - this is a documentation-only plugin
- Skills are markdown files with YAML frontmatter (`name`, `description`)
- Each skill directory has a `SKILL.md` entry point and `references/` subdirectory
- Plugin auto-installed via composer and claude-manager
