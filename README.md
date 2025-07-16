# AI 对话助手网页版

一个基于 HTML/CSS/JS 构建的轻量级 AI 对话网页应用，支持以下特性：

- 流式响应（打字机效果）
- 思考过程可视化（可折叠）
- Markdown 渲染（数学公式、代码高亮）
- 预设提示词（数学、编程、创意写作）
- 自定义系统提示词

---

## 快速开始

1. 克隆或直接下载本项目。  
2. 本地打开 `index.html` 即可使用。  
3. 首次使用请点击右上角 ⚙️ 图标配置：
   - **API Key**：你的 SiliconFlow 密钥  
   - **模型**：默认 `deepseek-ai/DeepSeek-V3`，可自行修改  
   - **提示词**：内置 4 种预设 + 自定义选项  

---

## 功能详解

| 功能 | 说明 |
|---|---|
| **流式响应** | 回答逐字实时显示，不卡顿。 |
| **思考过程** | 支持折叠深度思考思考过程。 |
| **Markdown** | 支持公式、代码块、列表、引用等。 |
| **提示词** | 默认 / 数学专家 / 编程专家 / 创意写作 / 自定义。 |

---

## 目录结构
```
.
├── index.html      # 单文件应用（含全部样式与脚本）
├── README.md       # 本说明文档
└── LICENSE         # MIT
```

---

## 常见问题

1. **必须联网吗？**  
   是的，需要连接 SiliconFlow API。

2. **能否嵌入到其他项目？**  
   可以，直接引入 `index.html` 或将样式与脚本拆分。

3. **如何修改样式？**  
   在 `<style>` 标签中调整 CSS 变量即可。

---

## 开发者指南

### 添加新的提示词预设

在 `PROMPT_PRESETS` 对象中追加：

```js
newPreset: {
    name: "新预设",
    content: "你是一名……"
}
```

## 许可证

GNU General Public License v3.0（GPLv3）

## 致谢

- [Marked.js](https://marked.js.org) – Markdown 渲染  
- [KaTeX](https://katex.org) – 数学公式  
- [Highlight.js](https://highlightjs.org) – 代码高亮  
- [SiliconFlow](https://siliconflow.cn) – 提供推理 API
