---
"@frontman-ai/frontman-protocol": minor
"@frontman-ai/frontman-client": minor
"@frontman-ai/client": minor
---

Add interactive question tool as a client-side MCP tool. Agents can ask users questions via a drawer UI with multi-step navigation, option selection, custom text input, and skip/cancel. Includes history replay ordering fixes (flush TextDeltaBuffer at message boundaries, use server timestamps for tool calls) and reconnect handling for interrupted questions (late tool result submission via session/tool_result, agent re-execution).
