<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }} API documentation</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
    </head>
    <body>
        {{-- Swagger UI renders public/openapi.yaml; "Try it out" sends real requests to the API. --}}
        <div id="swagger-ui"></div>

        <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
        <script>
            window.ui = SwaggerUIBundle({
                url: @js(asset('openapi.yaml')),
                dom_id: '#swagger-ui',
            });
        </script>
    </body>
</html>
