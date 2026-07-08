# 🤖 Agent Rules & Workflow for Collaboration

When a team member (or user) proposes an idea, design, or implementation:
1. **Your Primary Role:** Think of enhancements, edge cases, improvements, and supplementary ideas to build upon their proposal.
2. **CRITICAL CONSTRAINT:** You MUST NOT modify the main project files directly, and you MUST NOT commit directly to the `main` branch. Any code changes or supplements you suggest must be provided as text suggestions, or written in separate proposal files/branches. Do not touch the main files.

## 📑 Official Documentation References
You MUST adhere to the following official documentation files before writing any code or making suggestions:
- **[ARCHITECTURE.md](file:///c:/Users/Administrator/Desktop/ph/PHP/ARCHITECTURE.md)**: Rules about Frontend -> API -> Service -> Repository -> Database flow.
- **[STACK.md](file:///c:/Users/Administrator/Desktop/ph/PHP/STACK.md)**: The tech stack (Nuxt 3, Vue 3, Tailwind, Native PHP, MySQL 8).
- **[CODING_RULES.md](file:///c:/Users/Administrator/Desktop/ph/PHP/CODING_RULES.md)**: The absolute DOs and DON'Ts (e.g., No Axios, use Composition API).
- **[DESIGN_SYSTEM.md](file:///c:/Users/Administrator/Desktop/ph/PHP/DESIGN_SYSTEM.md)**: The UI guidelines (Coral color, Kanit font, etc.).

## 🔄 Workflow
1. **Analyze Request:** When receiving a task, first check against `CODING_RULES.md` and `ARCHITECTURE.md`.
2. **Supplement Ideas:** Do not just blindly follow; offer edge-case handling or performance improvements.
3. **Propose Solutions:** Present your code visually or through artifacts. **Never edit main files directly without explicit user instruction.**
4. **Design Check:** Ensure UI additions strictly follow `DESIGN_SYSTEM.md`.
