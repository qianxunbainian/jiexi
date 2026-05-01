
---

<span id="支持平台"></span>

<span id="支持平台"></span>
## 🌐 支持平台

| 平台                 | 接口文件           | 状态   |
|--------------------|----------------|------|
| **抖音** (TikTok 中国) | `douyin.php`   | ✅ 可用 |
| **快手**             | `kuaishou.php` | ✅ 可用 |
| **小红书**            | `xhsjx.php`    | ✅ 可用 |
| **汽水音乐**           | `dymusic.php`  | ✅ 可用 |
| **皮皮搞笑**           | `pipigx.php`   | ✅ 可用 |
| **皮皮虾**            | `ppxia.php`    | ✅ 可用 |
| **哔哩哔哩**           | `bilibili.php` | ✅ 可用 |
| **微博** 【接口版】       | `weibo.php`    | ✅ 可用 |
| **微博**             | `weibo_v.php`  | ✅ 可用 |
| **今日头条**           | `toutiao.php`  | ✅ 可用 |

---



<span id="使用说明"></span>
## 🚀 使用说明

### 基础用法

直接通过 URL 访问接口：

```plaintext
https://你的服务器地址/api/xxx.php?url=视频链接
```

### 请求示例

```plaintext
https://wzapi.com/api/douyin.php?url=https://v.douyin.com/xxxx/
```

### 响应示例

```json
{
  "code": 200,
  "msg": "解析成功",
  "data": {
    "type": "video",
    "title": "视频标题",
    "desc": "视频描述内容",
    "author": {
      "name": "作者名称",
      "id": "123456789",
      "avatar": "https://example.com/avatar.jpg"
    },
    "cover": "https://example.com/cover.jpg",
    "url": "https://example.com/video.mp4",
    "duration": 15000,
    "video_backup": [
      "https://example.com/video_backup_1.mp4",
      "https://example.com/video_backup_2.mp4"
    ],
    "images": [],
    "live_photo": [],
    "music": {
      "title": "背景音乐标题",
      "author": "背景音乐作者",
      "url": "https://example.com/music.mp3",
      "cover": "https://example.com/music_cover.jpg"
    },
    "video_id": "7489328058390000000"
  }
}
```

### 📱 抖音 Cookie 获取教程

**重要提示：** 抖音解析可能需要使用 Cookie 以提高解析成功率。

#### 获取步骤：

1. 打开浏览器，访问抖音网页版
2. 登录您的抖音账号
3. 按 F12 打开开发者工具
4. 切换到 Network 标签页
5. 刷新页面，找到一个请求
6. 在请求头中找到 Cookie 字段
7. 复制完整的 Cookie 值



<span id="接口文档"></span>
## 📖 接口文档

### 请求参数

| 参数名   | 类型  | 描述         | 是否必填 |
|-------|-----|------------|------|
| `url` | 字符串 | 短视频平台的视频链接 | ✅ 是  |

### 响应格式

| 字段                   | 类型    | 描述                                          |
|----------------------|-------|---------------------------------------------|
| `code`               | 整数    | 业务状态码 (`200` 成功，`400/404/500` 失败)           |
| `msg`                | 字符串   | 响应消息（便于直接展示错误原因）                            |
| `data`               | 对象/数组 | 返回数据主体（失败时可能为空数组）                           |
| `data.type`          | 字符串   | 内容类型：`video` / `image` / `live` / `unknown` |
| `data.title`         | 字符串   | 标题（通常与 `desc` 一致）                           |
| `data.desc`          | 字符串   | 描述文本                                        |
| `data.author`        | 对象    | 作者信息对象                                      |
| `data.author.name`   | 字符串   | 作者昵称                                        |
| `data.author.id`     | 字符串   | 作者唯一标识                                      |
| `data.author.avatar` | 字符串   | 作者头像 URL                                    |
| `data.cover`         | 字符串   | 封面图 URL                                     |
| `data.music`         | 对象    | 背景音乐信息对象                                    |
| `data.music.title`   | 字符串   | 背景音乐标题                                      |
| `data.music.author`  | 字符串   | 背景音乐作者                                      |
| `data.music.url`     | 字符串   | 背景音乐直链                                      |
| `data.music.cover`   | 字符串   | 背景音乐封面 URL                                  |
| `data.duration`      | 整数/空  | 视频时长（毫秒，可能为 `null`）                         |
| `data.url`           | 字符串/空 | 视频直链（`type=video` 时返回）                      |
| `data.video_backup`  | 数组    | 视频备选直链列表（`type=video`）                      |
| `data.video_id`      | 字符串   | 视频 ID（`type=video`）                         |
| `data.images`        | 数组    | 图集图片 URL 数组（`type=image/live`）              |
| `data.live_photo`    | 数组    | 实况图数组（`type=live`，每项包含 `image` 和 `video`）   |

### 状态码说明

| 状态码   | 描述     |
|-------|--------|
| `200` | 解析成功   |
| `400` | 请求参数错误 |
| `404` | 视频不存在  |
| `500` | 服务器错误  |

---

