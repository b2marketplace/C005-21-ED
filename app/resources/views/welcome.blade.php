<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Listado de productos</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <div id="app">
        <h1>Listado de productos</h1>
        <ul>
            <li v-for="product in products" :key="product.id">
                <strong>[[ product.sku ]]</strong> - [[ product.product_type ]] - [[ product.price ]] €
                <span v-if="product.status === 'COMPLETED'" style="color: green;">✔</span>
                <span v-else-if="product.status === 'FAILED'" style="color: red;">✖</span>
                <span v-else>[[ product.status ]]</span>
            </li>
        </ul>
    </div>
    <script>
    new Vue({
        el: '#app',
        delimiters: ['[[', ']]'],
        data: {
            products: []
        },
        created() {
            fetch('/api/products')
                .then(res => res.json())
                .then(data => { this.products = data; });
        }
    });
    </script>
</body>
</html>
