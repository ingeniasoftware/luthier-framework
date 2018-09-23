<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Luthier Framework</title>
        <link href="http://fonts.googleapis.com/css?family=Open+Sans:300,regular" rel="stylesheet" type="text/css">
        <link rel="shortcut icon" href="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/4QCMRXhpZgAATU0AKgAAAAgABwEaAAUAAAABAAAAYgEbAAUAAAABAAAAagEoAAMAAAABAAIAAAExAAIAAAARAAAAclEQAAEAAAABAQAAAFERAAQAAAABAAAAAFESAAQAAAABAAAAAAAAAAAAAABgAAAAAQAAAGAAAAABcGFpbnQubmV0IDQuMC4xNwAA/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8Aj8caZZ2E9tJbsRJKuGQtnhQAD/n0rlq9C8TXektfaXO08DyR3C7ypDYj5znHbOK5eX7DFpl9EstpNcNMrxyAPkrzkKcDkce3P0oA/9k=" />

        <style>
            body
            {
                background: white;
                margin: 0;
                padding: 0;
                font-family: "Open Sans", "Arial", "Helvetica", sans-serif
            }
            h1
            {
                font-weight: 300;
                font-size: 50px;
                margin: 0;
            }
            .container
            {
                padding-top: 100px;
                text-align: center;
                display: block;
                margin: 0px auto;
            }
            .version
            {
                color: silver;
            }
            a
            {
                text-decoration: none;
                color: #297EFF;
            }
            a:hover, a:focus
            {
                text-decoration: underline;
                color: #0A6CFF;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <img src="data:image/png;base64,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" width="100" height="100" alt="Luthier Framwork" />
            <h1>Luthier Framework</h1>
            <p class="version">Version <?= \Luthier\Framework::VERSION ;?></p>
            <p class="links">
                <a href="https://github.com/ingeniasoftware/luthier-framework">GitHub</a> &middot;
                <a href="https://luthier.ingenia.me/framework/en/docs">Documentation</a>
                <a href="https://luthier.ingenia.me/framework/es/docs">(Español)</a>
            </p>
        </div>
    </body>
</html>