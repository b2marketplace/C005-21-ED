![Proyecto financiado por la unión europea - Red.es - Plan de Recuperación, Transformación y Resilencia](assets/redes.png)

# Desarrollo de competencias contra la API de Amazon en PHP

Proyecto financiado por la Unión Europea - NextGenerationEU - Gobierno de España - Red.es - Plan de Recuperación, Transformación y Resilencia

## Descripción

Este proyecto nace de la experiencia adquirida durante el desarrollo de la subvención con código **2021/C005/00152950**, titulada "Motor algorítmico para posicionamiento en marketplaces". El propósito de este repositorio es contribuir y promocionar soluciones basadas en PHP que hemos desarrollado y perfeccionado durante el desarrollo del proyecto.

El software original se concibió como una plataforma de competición inteligente, utilizando inteligencia artificial y *machine learning* para optimizar la venta en grandes marketplaces. A través de este proyecto, compartimos parte de ese conocimiento y código con la comunidad.

## Objetivos del Proyecto

El objetivo principal de este proyecto es compartir una serie de herramientas y librerías en PHP que ayuden a otros desarrolladores a lidiar con los problemas derivados de la conexión con Amazon y las reglas para evitar ser, por un lado penalizados si superan las peticiones y por otro lado siendo responsables con el servidor e intentando que todo funcione de la mejor manera posible.

## Empezando

Para empezar a utilizar las herramientas de este proyecto, necesitarás tener un entorno de desarrollo PHP configurado.

Este repositorio contiene una imagen de docker que te permitirá hacer funcionar el proyecto pero necesitarás tus propias credenciales de Amazon SPAPI para poder ejecutarlo.

## Configuración de credenciales Amazon SP-API

Para que el proyecto funcione correctamente, necesitas crear un archivo de credenciales cifrado para Amazon SP-API. Sigue estos pasos:

1. Crea un archivo llamado `amazon_spapi_credentials.json.data` en la ruta `app/storage/app/`.
2. El contenido debe ser un JSON con la siguiente estructura:

```json
{
  "access_token": "",
  "token_type": "bearer",
  "expires_in": 3600,
  "generated_at": "2022-10-10 10:00:00", 
  "refresh_token": "",
  "selling_partner_id": "",
  "sts_credentials": {
    "access_key": "",
    "secret_key": "",
    "session_token": ""
  }
}
```

3. Antes de guardar el archivo, debes cifrar el contenido usando la Facade `Crypt` de Laravel. Por ejemplo:

```php
use Illuminate\Support\Facades\Crypt;

$contenido = json_encode($arrayCredenciales);
$contenidoCifrado = Crypt::encryptString($contenido);
file_put_contents('app/amazon_spapi_credentials.json.data', $contenidoCifrado);
```

El sistema leerá y descifrará automáticamente este archivo para obtener las credenciales necesarias en tiempo de ejecución.

## Uso del servicio de credenciales

Para obtener las credenciales de Amazon SP-API en cualquier parte de tu aplicación, simplemente inyecta la interfaz `AmazonSpApiCredentialsServiceInterface` en el constructor del servicio, controlador o clase donde lo necesites. Laravel resolverá automáticamente la implementación adecuada.

## Renovación de credenciales Amazon SP-API

Dependiendo de si tus credenciales son "autorizadas" (delegadas por un usuario de Amazon Seller Central) o "auto autorizadas" (propias de la cuenta de desarrollador), el modo de renovar las credenciales puede variar:

- **Credenciales autorizadas:** requieren un proceso de autorización OAuth y la renovación se realiza usando el `refresh_token`.
- **Credenciales auto autorizadas:** pueden requerir un flujo diferente, gestionado directamente por la cuenta de desarrollador.

Cuando el sistema detecta que las credenciales han expirado, lanza el evento `AmazonSpApiCredentialsExpired`. Si necesitas renovar las credenciales automáticamente, debes crear un listener para este evento y gestionar la renovación según el tipo de credencial.

## Cómo modificar el precio de un producto

Para iniciar el proceso de cambio de precio de un producto, utiliza el comando artisan:

```bash
docker compose exec web php artisan product:add {sku} {price} {marketplace_id}
```

- `{sku}`: SKU del producto en Amazon.
- `{price}`: Nuevo precio que deseas establecer.
- `{marketplace_id}`: (opcional, por defecto España: `A1RKKUPIHCS9HS`).

Este comando crea un nuevo registro de producto en estado `PENDING` y dispara el proceso automatizado de actualización de precio.

## Proceso de actualización de productos

El proceso de actualización de productos sigue una arquitectura basada en eventos y jobs, permitiendo una gestión desacoplada y escalable:

1. **Inicialización:**
   - Al ejecutar el comando para añadir un producto, se crea un nuevo registro en la base de datos con estado `PENDING`.
2. **Evento `ProductPriceChangePending`:**
   - Se dispara este evento tras la creación del producto.
3. **Listener:**
   - Un listener escucha el evento `ProductPriceChangePending` y lanza el job `GetProductType`.
4. **Job `GetProductType`:**
   - Consulta a Amazon el tipo de producto (`product_type`) necesario para poder realizar el cambio de precio.
   - El resultado se almacena en el producto y, tras ello, se lanza el evento `ProductTypeRetrieved`.
5. **Evento `ProductTypeRetrieved`:**
   - Indica que el tipo de producto ha sido recuperado correctamente.
6. **Listener:**
   - Un listener escucha el evento `ProductTypeRetrieved` y lanza el job `PerformPriceChange`.
7. **Job `PerformPriceChange`:**
   - Ejecuta la lógica de cambio de precio del producto en Amazon.
8. **Gestión de errores y reintentos:**
   - Los jobs gestionan automáticamente los reintentos en caso de throttling, expiración de credenciales o errores temporales.
   - Si es necesario renovar credenciales, se lanza el evento `AmazonSpApiCredentialsExpired`.


