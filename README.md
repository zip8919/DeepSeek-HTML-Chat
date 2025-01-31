# DeepSeekR1-ChatWeb

## 简介
DeepSeekR1-ChatWeb 是一个基于 PHP 7.4、HTML、CSS 和 JavaScript 的聊天网页应用。该项目旨在为用户提供一个简单的界面，用于与 DeepSeekR1 模型进行交互和测试。由于项目处于开发阶段，可能会存在不稳定的情况，请谨慎使用。
需要从Cloudflare获取到 API令牌 与 账户ID

## 功能
- 与 DeepSeekR1 模型进行实时聊天。
- 提供简单的用户界面。
- 支持多轮对话。
- 显示模型生成的回复。

## 技术栈
- 前端：
  - HTML
  - CSS
  - JavaScript
- 后端：
  - PHP 7.4

## 环境依赖
- PHP 7.4+
- 一台能开机的服务器

## 部署方法
- 在 https://github.com/baicaizhale/DeepSeekR1-ChatWeb/releases 中下载到最新版本
- 解压到 /wwwroot 目录下(也可以是其他您认为可以访问的位置)
- 访问 https://dash.cloudflare.com/ 依次 AI - Workers AI - 模型 - 使用 REST API - 点击![image](https://github.com/user-attachments/assets/24de090a-ba77-439d-8e99-c93a648e18f0)
  ,填写在'account_id'后 - 创建 Workers AI API 令牌 - 创建 API 令牌 - 点击![image](https://github.com/user-attachments/assets/4fce16e3-c692-44bc-afde-53f67aba49cf),填写在'api_token'后
-结束,现在访问您的服务器
