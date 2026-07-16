<p align="center">
  <img src="https://img.shields.io/badge/WordPress-21759B?style=for-the-badge&logo=wordpress&logoColor=white" alt="WordPress">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/WPML-Compatible-00A0D2?style=for-the-badge" alt="WPML Compatible">
  <img src="https://img.shields.io/badge/AJAX-Optimized-222A35?style=for-the-badge" alt="AJAX Optimized">
</p>

<h1 align="center">🌍 WPLanguageCheck: Sugerencia Inteligente de Idioma</h1>

<p align="center">
  <strong>Plugin ligero para sugerir al visitante la versión regional más adecuada de la web.</strong><br>
  Detecta el país del usuario, compara su idioma actual y muestra un aviso discreto para cambiar de región cuando corresponde.
</p>

---

### ✨ Características Principales

*   **🌐 Sugerencia de idioma por país:** Recomienda la versión regional adecuada según el código de país del visitante.
*   **☁️ Compatible con Cloudflare:** Prioriza `HTTP_CF_IPCOUNTRY` para obtener el país de forma rápida y fiable.
*   **⚡ Carga optimizada por AJAX:** El contexto del banner se solicita solo si no existe la cookie `wp_language_check` y 5 segundos después de cargar la web.
*   **🍪 Control por cookie:** Si el usuario acepta, continúa o cierra el aviso, se guarda la decisión y no se vuelve a mostrar.
*   **🎯 Redirección directa a homes regionales:** Envía al usuario a la home configurada del idioma sugerido.
*   **🧩 Configuración centralizada:** Idiomas, rutas, países y labels se controlan desde una única configuración en el plugin.
*   **🛡️ No intrusivo para administradores:** El banner no se carga para administradores logeados.
*   **🧪 Debug en consola:** Incluye logs útiles con país, IP, idioma detectado, cookie de WPML y URL sugerida.

---

### 🚀 Instalación Rápida

1.  Sube la carpeta `WPLanguageCheck` al directorio `/wp-content/plugins/`.
2.  Activa el plugin desde el menú 'Plugins' en WordPress.
3.  Revisa la configuración de idiomas y países en `WPLanguageCheck.php`.
4.  Comprueba que las rutas regionales existen en la web.

---

### 🧭 Idiomas y Rutas Soportadas

El plugin trabaja con las siguientes versiones regionales:

| Idioma | Ruta Home | Región mostrada |
| :--- | :--- |
| `en` | `/` | England |
| `en-usa` | `/en-usa/` | United States |
| `es` | `/es/` | Spain |
| `ca` | `/ca/` | Catalonia |
| `fr` | `/fr/` | France |
| `de` | `/de/` | Germany |
| `ru` | `/ru/` | Russia |

---

### ⚙️ Workflow del Plugin

1.  El plugin carga sus assets en frontend, excepto para administradores logeados.
2.  El JavaScript espera a que la web termine de cargar.
3.  Pasados 5 segundos, comprueba si existe la cookie `wp_language_check`.
4.  Si la cookie existe, no hace nada.
5.  Si la cookie no existe, solicita por AJAX el contexto del visitante.
6.  PHP obtiene el país usando Cloudflare (`HTTP_CF_IPCOUNTRY`) y, si no está disponible, fallback por IP.
7.  Se calcula el idioma recomendado según el mapeo centralizado de países.
8.  Se compara el idioma actual con el idioma sugerido.
9.  Si son diferentes, se muestra el banner de confirmación de ubicación.
10. Si el usuario acepta, se guarda la cookie y se redirige a la home regional sugerida.
11. Si el usuario continúa o cierra, se guarda la cookie y permanece en la versión actual.

---

### 🧩 Configuración Centralizada

La configuración principal vive en `wplanguagecheck_get_languages_config()` dentro de `WPLanguageCheck.php`.

Desde ahí se controla:

*   **Ruta home** de cada idioma.
*   **Países asignados** a cada versión regional.
*   **Label visible** en los botones del banner.
*   **Idioma por defecto** de la web.

Ejemplo conceptual:

```php
'es' => [
    'slug' => 'es',
    'home_path' => '/es/',
    'countries' => ['ES'],
    'label' => 'Spain',
]
```

---

### 🎨 Banner de Confirmación

El aviso está diseñado como una tarjeta discreta inspirada en el selector regional de Tesla:

*   Aparece en la parte superior derecha.
*   No bloquea la navegación de la web.
*   No oscurece la página con un overlay intrusivo.
*   Muestra la región actual y la región sugerida.
*   Permite aceptar la sugerencia, continuar navegando o cerrar el aviso.

---

### 🌍 Traducciones

Las traducciones están disponibles en la carpeta `lang/`.

Idiomas incluidos:

*   `en.php`
*   `en-usa.php`
*   `es.php`
*   `ca.php`
*   `fr.php`
*   `de.php`
*   `ru.php`

> [!TIP]
> Si añades un nuevo label en la configuración, recuerda añadir su traducción en todos los archivos necesarios de `lang/`.

---

### 🛠️ Debug

El plugin muestra logs en la consola del navegador con el prefijo `[WPLanguageCheck]`.

Estos logs ayudan a revisar:

*   Si existe la cookie `wp_language_check`.
*   El país detectado.
*   La IP del visitante.
*   El idioma actual detectado.
*   La cookie `wp-wpml_current_language`.
*   La URL regional sugerida.
*   El contenido renderizado en el banner.

---

### 📌 Notas Importantes

*   El banner es una ayuda de UX, no una herramienta SEO.
*   Las versiones regionales deben seguir siendo accesibles mediante URLs normales.
*   Para SEO internacional, se recomienda mantener correctamente configurados los `hreflang`, sitemaps y enlaces internos de idioma.
*   Los botones del banner son botones de interacción, no enlaces pensados para robots.

---

<p align="center">
  Hecho con ❤️ para ofrecer una experiencia regional más clara y menos intrusiva.
</p>