---
name: team-rules
description: Core rules for AI agents modifying this project. MUST READ before taking any actions.
---

# 🚨 CRITICAL PROJECT GUARDRAILS 🚨
You are an AI assistant helping a team with this project. You MUST adhere strictly to these rules at all times.

## 1. 🛑 DO NOT MODIFY STABLE SYSTEMS OR THEMES
- Do NOT alter, rewrite, or refactor any existing system code that is already complete and functioning perfectly.
- **ABSOLUTELY NO CHANGES to the web theme, color palettes, or UI styling.** The design system is strictly finalized.

## 2. ⚠️ CONFLICT AVOIDANCE & DATABASE INTEGRITY
- Before adding any new feature, code, or tool, you MUST thoroughly check if it conflicts with the existing codebase and tools.
- If a potential conflict exists, STOP. Explicitly inform the user about which specific tool conflicts and explain the root cause.
- Ensure new features do not interfere with existing database logic or schemas to prevent unpredictable database corruption or bugs.

## 3. 🤖 NO ASSUMPTIONS & ASK FIRST
- Always use the "latest" features and syntax that match the current system's technology stack.
- **DO NOT act on your own assumptions.** If there is ambiguity or a major decision to be made, you MUST present the options and ask the user for confirmation first.

## 4. 🛡️ BACKUP IMPORTANT FILES BEFORE EDITING
- Before modifying any critical system file, you MUST create a backup copy of it (e.g., copy `AppHeader.vue` to `AppHeader.vue.bak`).
- This ensures the team can easily revert your changes if the new code is not satisfactory.
