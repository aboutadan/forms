Estás desarrollando un sistema de formularios dinámicos tipo Google Forms usando PHP 8+ y MySQL 8.

OBJETIVO
Permitir que usuarios autenticados puedan:

1. Crear formularios personalizados con las preguntas que consideren necesarias
2. Guardar formularios como borradores
3. Publicar formularios (congelar la estructura al momento de publicar)
4. Reutilizar formularios publicados de manera ilimitada
5. Recibir múltiples envíos por cada formulario

RESTRICCIONES IMPORTANTES

- Utilizar ÚNICAMENTE DOS TABLAS PRINCIPALES:
  1. formularios
  2. envios_formulario
- NO crear tablas por pregunta ni por respuesta
- Usar JSON para la estructura del formulario y para las respuestas
- Toda la lógica de validación y renderizado debe hacerse en PHP, NO en SQL

DISEÑO DE BASE DE DATOS

Tabla: formularios

- id (PK)
- propietario_id
- titulo
- esquema (JSON) -- borrador editable
- esta_publicado (boolean)
- esquema_publicado (JSON) -- estructura congelada
- publicado_en (timestamp)
- creado_en (timestamp)

Tabla: envios_formulario

- id (PK)
- formulario_id (FK)
- respuestas (JSON)
- enviado_en (timestamp)

FORMATO DEL ESQUEMA DEL FORMULARIO (JSON)
Cada formulario debe incluir:

- preguntas: arreglo de objetos

Cada pregunta debe incluir:

- id (string, identificador estable)
- tipo (text, textarea, email, number, select, radio, checkbox, date)
- etiqueta
- requerido (boolean)
- opciones (arreglo, solo para select / radio / checkbox)

Ejemplo:
{
"preguntas": [
{
"id": "nombre_completo",
"tipo": "text",
"etiqueta": "Nombre completo",
"requerido": true
},
{
"id": "departamento",
"tipo": "select",
"etiqueta": "Departamento",
"opciones": ["RH", "TI", "Finanzas"]
}
]
}

REGLAS DE COMPORTAMIENTO (CRÍTICAS)

- Los formularios en borrador usan el campo `esquema`
- Los formularios públicos SIEMPRE deben renderizarse usando `esquema_publicado`
- Las respuestas deben validarse únicamente contra `esquema_publicado`
- Editar un borrador NO debe afectar envíos existentes
- Publicar un formulario sobrescribe `esquema_publicado` con el contenido actual de `esquema`

REQUERIMIENTOS EN PHP
Implementar ejemplos de:

1. Guardar formulario como borrador
2. Publicar formulario (copiar esquema → esquema_publicado)
3. Renderizar formulario publicado de forma dinámica
4. Validar envíos según el esquema publicado
5. Guardar respuestas como JSON usando el id de cada pregunta

Ejemplo de respuestas (JSON):
{
"nombre_completo": "Ana Gómez",
"departamento": "TI"
}

NO HACER

- Normalizar preguntas o respuestas en filas
- Usar patrones EAV
- Crear tablas de analítica
- Sobreingeniería

RESULTADO ESPERADO

- Definiciones SQL de las tablas
- Ejemplos en PHP para:
  - Publicar un formulario
  - Renderizar un formulario
  - Validar un envío
  - Guardar un envío
- Código claro, mínimo y listo para producción
