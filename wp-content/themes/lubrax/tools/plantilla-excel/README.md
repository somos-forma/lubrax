# Plantilla Excel de importación

Genera `assets/import/plantilla-contenido-web.xlsx` (vacía) o un Excel con los datos actuales del sitio, con listas desplegables
(categorías, viscosidades, especificaciones, envases, regiones y comuna dependiente de la región).

Requiere Python 3 con `openpyxl` (`pip install openpyxl`).

```bash
# 1. Exportar datos y listas de WordPress
wp eval-file wp-content/themes/lubrax/tools/plantilla-excel/export_data.php > export.json

# 2a. Plantilla vacía (la que descarga el importador)
python wp-content/themes/lubrax/tools/plantilla-excel/make_import_xlsx.py export.json wp-content/themes/lubrax/assets/import/plantilla-contenido-web.xlsx --vacia

# 2b. Excel con los datos actuales (respaldo o edición masiva)
python wp-content/themes/lubrax/tools/plantilla-excel/make_import_xlsx.py export.json datos-actuales.xlsx
```

Regenera la plantilla vacía cuando cambien los segmentos, tipos, viscosidades, especificaciones o envases,
para que sus listas desplegables queden al día.
