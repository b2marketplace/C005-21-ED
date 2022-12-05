<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Listado de productos</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div id="app" class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h4 mb-0">Listado de productos</h1>
                    </div>
                    <div class="card-body">
                        <p v-if="products.length === 0" class="text-center text-muted my-4">No se han encontrado productos.</p>
                        <div class="table-responsive" v-if="products.length > 0">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>SKU</th>
                                        <th>Tipo</th>
                                        <th>Precio</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in paginatedProducts" :key="product.id">
                                        <td><strong>[[ product.sku ]]</strong></td>
                                        <td><span class="badge badge-info">[[ product.product_type ]]</span></td>
                                        <td class="font-weight-bold text-right">[[ product.price ]] €</td>
                                        <td>
                                            <span v-if="product.status === {{ \App\Models\Product::STATUS_COMPLETED }}" class="badge badge-success">✔ Completado</span>
                                            <span v-else-if="product.status === {{ \App\Models\Product::STATUS_FAILED }}" class="badge badge-danger">✖ Fallado</span>
                                            <span v-else-if="product.status === {{ \App\Models\Product::STATUS_PENDING }}" class="badge badge-warning">Pendiente</span>
                                            <span v-else-if="product.status === {{ \App\Models\Product::STATUS_IN_PROGRESS }}" class="badge badge-primary">En progreso</span>
                                            <span v-else-if="product.status === {{ \App\Models\Product::STATUS_EXPIRED }}" class="badge badge-dark">Expirado</span>
                                            <span v-else-if="product.status === {{ \App\Models\Product::STATUS_CANCELLED }}" class="badge badge-secondary">Cancelado</span>
                                            <span v-else class="badge badge-light">[[ product.status ]]</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <nav v-if="totalPages > 1" aria-label="Paginación de productos">
                            <ul class="pagination justify-content-center my-3">
                                <li class="page-item" :class="{disabled: currentPage === 1}">
                                    <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Anterior</a>
                                </li>
                                <li class="page-item" v-for="page in totalPages" :key="page" :class="{active: currentPage === page}">
                                    <a class="page-link" href="#" @click.prevent="goToPage(page)">[[ page ]]</a>
                                </li>
                                <li class="page-item" :class="{disabled: currentPage === totalPages}">
                                    <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Siguiente</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    new Vue({
        el: '#app',
        delimiters: ['[[', ']]'],
        data: {
            products: [],
            currentPage: 1,
            perPage: 10,
            totalPages: 1,
            total: 0
        },
        computed: {
            paginatedProducts() {
                return this.products;
            }
        },
        methods: {
            goToPage(page) {
                if (page < 1 || page > this.totalPages) return;
                this.currentPage = page;
                this.fetchProducts();
            },
            fetchProducts() {
                fetch(`/api/products?page=${this.currentPage}&per_page=${this.perPage}`)
                    .then(res => res.json())
                    .then(data => {
                        this.products = data.data;
                        this.totalPages = data.last_page;
                        this.total = data.total;
                    });
            }
        },
        created() {
            this.fetchProducts();
        }
    });
    </script>

</body>
</html>
