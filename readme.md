# Zerojudge Statistic Updater Over Google SpreadSheets

本程式能夠更新一個 Google SpreadSheets 內的 Zerojudge 帳號的統計資訊。

This program can update Zerojudge Account Statistic in a Google SpreadSheets.

# Usage

## Installation

* git clone source code from github

```sh
git clone git@github.com:taichunmin/zj_rank2.git
```

* install requirement with [composer](https://getcomposer.org/)

```sh
composer install
```

## Google SpreadSheets Worksheets

* 範本：<https://drive.google.com/previewtemplate?id=1WhZzUp8EerIuFprOIG7ZaSK2mtR4Jz3TVzu0vQlsl90&mode=public>

> 記得將 Worksheets 公開發佈到網路上

## Configure

設定檔位於 `src/` 資料夾內 `config.php`，請將 `config.sample.php` 複製成 `config.php` 後，再行填寫裡面的資料。

* Zerojudge Config

由於現在 [Zerojudge](http://zerojudge.tw) 必須要先登入才能夠查詢使用者統計資料，所以必須給程式一組帳號密碼用來登入 Zerojudge。

> 經測試，`guest/guest` 無法查看使用者統計資料。

<table>
	<tr>
		<th>ZEROJUDGE_ACCOUNT</th>
		<td>Zerojudge 的帳號</td>
	</tr>
	<tr>
		<th>ZEROJUDGE_PASSWD</th>
		<td>Zerojudge 的密碼</td>
	</tr>
</table>

* Google Developers Config

由於需要動態更新到 Google SpreadSheets，所以程式需要透過 Google API 存取您的 Google SpreadSheets。 請至 [Google Developers Console](https://console.developers.google.com/) 建立一個專案並取得 Google Client ID 和 Google Client Secret。 由於本程式使用 Google Oauth2 的 Device 方式登入，故不需填寫 `重新導向 URI`。

> Google 官方文件： <https://developers.google.com/google-apps/spreadsheets/#authorizing_requests_with_oauth_20>

以下均為假資料。

<table>
	<tr>
		<th>GOOGLE_CLIENT_ID</th>
		<td>12345678.apps.googleusercontent.com</td>
	</tr>
	<tr>
		<th>GOOGLE_CLIENT_SECRET</th>
		<td>Gc0230jdsah01jqpowpgff</td>
	</tr>
</table>

* Google SpreadSheets Config

這兩個欄位需要填寫你想要動態更新的 SpreadSheets ID 中的 Worksheets ID。 查詢步驟如下：

1. 先在網頁端開啟你想要的 SpreadSheets，然後複製網址如下：

```
https://docs.google.com/spreadsheets/d/1Qg5PIPiKgufZFsKdbc185FP4DX1k2X_lNlLJXzyAKkY/edit#gid=336254113
```

2. 在網址中間就會有 SpreadSheets ID

```
1Qg5PIPiKgufZFsKdbc185FP4DX1k2X_lNlLJXzyAKkY
```

3. 透過 SpreadSheets ID 連結以下網址取得所有的 Worksheets

> 請記得，將底下的 SpreadSheets ID 替換成你想要的。

```
https://spreadsheets.google.com/feeds/worksheets/1Qg5PIPiKgufZFsKdbc185FP4DX1k2X_lNlLJXzyAKkY/private/full
```

4. 找到你想要的 Worksheets ID

```
https://spreadsheets.google.com/feeds/list/1Qg5PIPiKgufZFsKdbc185FP4DX1k2X_lNlLJXzyAKkY/o5k73i3/private/full
```

在這資料之中，`o5k73i3` 就是想要的 Worksheets ID。

5. 記得將 Worksheets 公開發佈到網路上

<table>
	<tr>
		<th>GOOGLE_SPREADSHEET_ID</th>
		<td>1Qg5PIPiKgufZFsKdbc185FP4DX1k2X_lNlLJXzyAKkY</td>
	</tr>
	<tr>
		<th>GOOGLE_WORKSHEET_ID</th>
		<td>o5k73i3</td>
	</tr>
</table>

## Execute Test

```sh
cd zj_rank2/
./cron.sh
```

* 本程式需要使用 Google Oauth2 Device 授權，如果出現以下文字：

```
Please goto the URL: https://www.google.com/device
Enter The User Code: GQVQ-JKEC
```

請用瀏覽器開啟 <https://www.google.com/device>，登入 Google 帳戶後，使用 `User Code` 進行授權。


## Crontab 定期執行

本程式可設定使用 crontab 定期執行。

* 進入 crontab 設定頁面

```sh
crontab -e
```

* 設定如下 crontab 設定

若要每個小時執行一次，設定檔如下：

```sh
0 * * * * /home/taichunmin/zj_rank2/cron.sh > /home/taichunmin/zj_rank2/last.log 2>&1
```

## 修改呈現頁面

使用 Bootstrap 製作的 Responsive Web Design 頁面位於 `src/Zerojudge_Ranking.html`，請自行修改程式碼的 `title`, `h1` 及以下的 `GOOGLE_SPREADSHEET_ID/GOOGLE_WORKSHEET_ID`。

```js
var zj_rank = new Gsx('GOOGLE_SPREADSHEET_ID/GOOGLE_WORKSHEET_ID', function());
```

## 授權

本程式主體採用 `MIT` 授權。 其餘使用到的函示庫列舉如下：

<table>
	<tr>
		<th>套件名稱</th>
		<td>授權方式</td>
	</tr>
	<tr>
		<th>taichunmin/php-google-spreadsheet-client</th>
		<td>MIT</td>
	</tr>
	<tr>
		<th>technosophos/querypath</th>
		<td>MIT</td>
	</tr>
	<tr>
		<th>google/google-api-php-client</th>
		<td>Apache 2.0</td>
	</tr>
</table>
