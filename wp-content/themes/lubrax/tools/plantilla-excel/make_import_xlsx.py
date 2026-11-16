"""Genera la plantilla de importación de Lubrax con listas desplegables (y comuna dependiente de la región).

Uso: python make_import_xlsx.py export.json salida.xlsx [--vacia]
  export.json: datos y listas exportados de WordPress (export_data.php).
  --vacia: solo encabezados y listas (plantilla para el cliente), sin los datos actuales.
"""
import json
import sys

from openpyxl import Workbook
from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
from openpyxl.utils import get_column_letter
from openpyxl.workbook.defined_name import DefinedName
from openpyxl.worksheet.datavalidation import DataValidation

SRC, OUT = sys.argv[1], sys.argv[2]
EMPTY = '--vacia' in sys.argv
data = json.load(open(SRC, encoding='utf8'))

FIRST, LAST = 6, 505  # Filas de datos (500 registros por hoja).
GREEN, GREEN_DARK, YELLOW, PAPER = '38761D', '166D0C', 'F5EC00', 'F3FDEE'
thin = Side(style='thin', color='C9D3C4')

wb = Workbook()

# ---------------------------------------------------------------- Listas (hoja oculta)
lists = wb.active
lists.title = 'Listas'
L = data['lists']
combustible = L['Tipo combustible'] + [', '.join(L['Tipo combustible'])]
tipo_compra = L['Tipo de compra'] + [', '.join(L['Tipo de compra'])]
regions = list(data['comunas'].keys())

columns = [
    ('Lista_Categoria', 'Categoría', L['Categoría']),
    ('Lista_TipoProducto', 'Tipo de producto', L['Tipo de producto']),
    ('Lista_Combustible', 'Tipo combustible', combustible),
    ('Lista_Viscosidad', 'Grado de viscosidad', L['Grado de viscosidad']),
    ('Lista_Especificaciones', 'Especificaciones', L['Especificaciones']),
    ('Lista_Base', 'Base', L['Base']),
    ('Lista_Envases', 'Envases', L['Envases']),
    ('Lista_Capacidad', 'Capacidad logística', L['Capacidad logística']),
    ('Lista_TipoCompra', 'Tipo de compra', tipo_compra),
    ('Lista_SiNo', 'Destacado', ['Sí', 'No']),
    ('Lista_Regiones', 'Región', regions),
]
col = 1
for name, title, values in columns:
    letter = get_column_letter(col)
    lists.cell(1, col, title).font = Font(bold=True)
    for i, v in enumerate(values, start=2):
        lists.cell(i, col, v)
    wb.defined_names[name] = DefinedName(name, attr_text=f"Listas!${letter}$2:${letter}${len(values) + 1}")
    lists.column_dimensions[letter].width = 26
    col += 1

# Tabla región → nombre del rango de sus comunas (para la lista dependiente).
map_col = col
lists.cell(1, map_col, 'Región').font = Font(bold=True)
lists.cell(1, map_col + 1, 'Rango de comunas').font = Font(bold=True)
first_comuna_col = map_col + 3
for i, region in enumerate(regions):
    range_name = f'Comunas_{i + 1:02d}'
    lists.cell(i + 2, map_col, region)
    lists.cell(i + 2, map_col + 1, range_name)
    c = first_comuna_col + i
    letter = get_column_letter(c)
    lists.cell(1, c, region).font = Font(bold=True)
    for j, comuna in enumerate(data['comunas'][region], start=2):
        lists.cell(j, c, comuna)
    wb.defined_names[range_name] = DefinedName(range_name, attr_text=f"Listas!${letter}$2:${letter}${len(data['comunas'][region]) + 1}")
    lists.column_dimensions[letter].width = 22
ml, ml2 = get_column_letter(map_col), get_column_letter(map_col + 1)
wb.defined_names['Mapa_Regiones'] = DefinedName('Mapa_Regiones', attr_text=f"Listas!${ml}$2:${ml2}${len(regions) + 1}")
lists.column_dimensions[ml].width = 40
lists.sheet_state = 'hidden'


# ---------------------------------------------------------------- Hojas de datos
def sheet(title, intro, headers, rows, color):
    ws = wb.create_sheet(title)
    n = len(headers)
    ws.merge_cells(start_row=1, start_column=1, end_row=1, end_column=n)
    ws.merge_cells(start_row=2, start_column=1, end_row=4, end_column=n)
    ws['A1'] = f'{title.upper()} - PLANTILLA DE CARGA DE DATOS'
    ws['A1'].font = Font(bold=True, size=14, color='FFFFFF')
    ws['A1'].fill = PatternFill('solid', fgColor='032B1A')
    ws['A1'].alignment = Alignment(vertical='center', indent=1)
    ws.row_dimensions[1].height = 28
    ws['A2'] = intro
    ws['A2'].alignment = Alignment(wrap_text=True, vertical='top', indent=1)
    ws['A2'].fill = PatternFill('solid', fgColor=PAPER)
    for c, (header, width, _, required) in enumerate(headers, start=1):
        cell = ws.cell(5, c, header)
        cell.font = Font(bold=True, color='FFFFFF')
        cell.fill = PatternFill('solid', fgColor=color if not required else '0B3D1E')
        cell.alignment = Alignment(wrap_text=True, vertical='center', horizontal='center')
        cell.border = Border(left=thin, right=thin, top=thin, bottom=thin)
        ws.column_dimensions[get_column_letter(c)].width = width
    ws.row_dimensions[5].height = 45
    for r, row in enumerate(rows, start=FIRST):
        for c, value in enumerate(row, start=1):
            cell = ws.cell(r, c, value)
            cell.alignment = Alignment(wrap_text=True, vertical='top')
    for r in range(FIRST, FIRST + max(len(rows), 1) + 20):
        for c in range(1, n + 1):
            ws.cell(r, c).border = Border(bottom=thin)
    ws.freeze_panes = 'B6'
    ws.sheet_view.zoomScale = 90
    return ws


def validate(ws, col, formula, prompt, strict=True, title=None):
    letter = get_column_letter(col)
    dv = DataValidation(type='list', formula1=formula, allow_blank=True, showDropDown=False)
    dv.showErrorMessage = strict
    dv.errorStyle = 'stop'
    dv.errorTitle = 'Valor no válido'
    dv.error = 'Elige un valor de la lista desplegable.'
    dv.showInputMessage = True
    dv.promptTitle = (title or ws.cell(5, col).value)[:32]
    dv.prompt = prompt[:255]
    dv.add(f'{letter}{FIRST}:{letter}{LAST}')
    ws.add_data_validation(dv)


def note(ws, col, prompt):
    letter = get_column_letter(col)
    dv = DataValidation(type='custom', formula1='TRUE', allow_blank=True)
    dv.showInputMessage = True
    dv.promptTitle = ws.cell(5, col).value[:32]
    dv.prompt = prompt[:255]
    dv.add(f'{letter}{FIRST}:{letter}{LAST}')
    ws.add_data_validation(dv)


MULTI = 'Elige uno de la lista o escribe varios separados por coma (por ejemplo: «{}»).'

# Productos: (encabezado, ancho, clave en el JSON, obligatorio)
PROPS = ['Índice de viscosidad', 'Viscosidad a 100 °C', 'Viscosidad a 40 °C', 'Punto de inflamación', 'Punto de fluidez', 'Intervalo de cambio sugerido']
prod_headers = [
    ('Nombre Producto', 30, 'Nombre Producto', True),
    ('SKU', 14, 'SKU', False),
    ('Categoría', 22, 'Categoría', True),
    ('Tipo de producto', 26, 'Tipo de producto', False),
    ('Tipo combustible', 18, 'Tipo combustible', False),
    ('Grado de viscosidad', 16, 'Grado de viscosidad', False),
    ('Especificaciones, Normativas o Certificaciones', 30, 'Especificaciones, Normativas o Certificaciones', False),
    ('Base', 15, 'Base', False),
    ('Presentación Envase', 26, 'Presentación Envase', False),
    ('Aplicaciones recomendadas', 40, 'Aplicaciones recomendadas', False),
    ('Normas y homologaciones', 30, 'Normas y homologaciones', False),
    ('Descripción corta', 40, 'Descripción corta', False),
    ('Descripción', 60, 'Descripción', False),
    ('Ruta url de imagen', 40, 'Ruta url de imagen', False),
    ('Ficha técnica', 40, 'Ficha técnica', False),
    ('Hoja de seguridad (sds)', 40, 'Hoja de seguridad (sds)', False),
    ('Distribuido por', 40, 'Distribuido por', False),
    ('Destacado', 12, 'Destacado', False),
    ('Índice de viscosidad', 14, 'prop:Índice de viscosidad', False),
    ('Viscosidad a 100 °C (mm²/s)', 16, 'prop:Viscosidad a 100 °C', False),
    ('Viscosidad a 40 °C (mm²/s)', 16, 'prop:Viscosidad a 40 °C', False),
    ('Punto de inflamación (°C)', 16, 'prop:Punto de inflamación', False),
    ('Punto de fluidez (°C)', 14, 'prop:Punto de fluidez', False),
    ('Intervalo de cambio sugerido', 22, 'prop:Intervalo de cambio sugerido', False),
]


def value(record, key):
    if key.startswith('prop:'):
        return record.get('props', {}).get(key[5:], '')
    return record.get(key, '')


prod_rows = [] if EMPTY else [[value(p, h[2]) for h in prod_headers] for p in data['products']]
products = sheet(
    'Productos',
    'Una fila por producto. Las columnas con encabezado verde oscuro son obligatorias. Usa las listas desplegables: '
    'donde se aceptan varios valores, sepáralos con coma. Imágenes y PDF: enlace público (Google Drive, Dropbox o web). '
    'No cambies el texto de los encabezados: el importador reconoce las columnas por su título. Lee la hoja «Instrucciones».',
    prod_headers, prod_rows, GREEN)
c = {h[0]: i for i, h in enumerate(prod_headers, start=1)}
validate(products, c['Categoría'], 'Lista_Categoria', 'Segmento del catálogo. Elige uno de la lista.')
validate(products, c['Tipo de producto'], 'Lista_TipoProducto', 'Elige uno de la lista.')
validate(products, c['Tipo combustible'], 'Lista_Combustible', 'Gasolina, diésel o ambos. Vacío si no aplica (aceites industriales, transmisiones).')
validate(products, c['Grado de viscosidad'], 'Lista_Viscosidad', 'Grado SAE o ISO VG. Si no está en la lista, escríbelo: se creará al importar.', strict=False)
validate(products, c['Especificaciones, Normativas o Certificaciones'], 'Lista_Especificaciones', MULTI.format('API SP, ILSAC GF-6A') + ' Las nuevas se crean al importar.', strict=False, title='Especificaciones')
validate(products, c['Base'], 'Lista_Base', 'Mineral, semisintético o sintético.')
validate(products, c['Presentación Envase'], 'Lista_Envases', MULTI.format('1 L, 4 L, 20 L'), strict=False)
validate(products, c['Distribuido por'], "Distribuidores!$A$6:$A$505", MULTI.format('Lubricantes del Maule, Lubricantes Araucanía') + ' Nombres de la hoja Distribuidores.', strict=False)
validate(products, c['Destacado'], 'Lista_SiNo', '«Sí» lo muestra en «Productos destacados» del inicio.')
note(products, c['Nombre Producto'], 'Obligatorio. Si el producto ya existe (mismo nombre o SKU), se actualiza.')
note(products, c['SKU'], 'Opcional. Si lo completas, el producto se identifica por SKU (permite cambiar el nombre).')
note(products, c['Aplicaciones recomendadas'], 'Una aplicación por línea (Alt + Enter para un salto de línea en la celda).')
for h in ('Ruta url de imagen', 'Ficha técnica', 'Hoja de seguridad (sds)'):
    note(products, c[h], 'Enlace público al archivo (Google Drive o Dropbox compartido «cualquier persona con el enlace», o una URL web). Imagen: JPG, PNG o WebP. Ficha y SDS: PDF.')

# Distribuidores
dist_headers = [
    ('Nombre Distribuidor', 30, 'Nombre Distribuidor', True),
    ('Zona', 18, 'Zona', False),
    ('Tipo de compra', 22, 'Tipo de compra', False),
    ('Dirección', 40, 'Dirección', False),
    ('Región', 34, 'Región', True),
    ('Comuna', 24, 'Comuna', True),
    ('Link google maps', 40, 'Link google maps', False),
    ('Latitud', 12, 'Latitud', False),
    ('Longitud', 12, 'Longitud', False),
    ('Teléfono', 18, 'Teléfono', False),
    ('Correo', 28, 'Correo', False),
    ('Horario', 30, 'Horario', False),
    ('Página web', 30, 'Página web', False),
    ('Portafolio de productos', 50, 'Portafolio de productos', False),
    ('Capacidad logística', 30, 'Capacidad logística', False),
    ('Presentaciones que ofrece', 28, 'Presentaciones que ofrece', False),
]


def dist_value(record, key):
    v = record.get(key, '')
    if key in ('Latitud', 'Longitud') and v not in ('', None):
        return float(v)
    return v


dist_rows = [] if EMPTY else [[dist_value(d, h[2]) for h in dist_headers] for d in data['distributors']]
dists = sheet(
    'Distribuidores',
    'Una fila por distribuidor. Elige primero la Región: la lista de Comuna muestra solo las comunas de esa región. '
    'Las coordenadas (latitud y longitud) se completan solas si el enlace de Google Maps las incluye. '
    'Donde se aceptan varios valores, sepáralos con coma. No cambies el texto de los encabezados.',
    dist_headers, dist_rows, GREEN_DARK)
d = {h[0]: i for i, h in enumerate(dist_headers, start=1)}
region_letter = get_column_letter(d['Región'])
validate(dists, d['Tipo de compra'], 'Lista_TipoCompra', 'Mayorista, minorista o ambos.')
validate(dists, d['Región'], 'Lista_Regiones', 'Elige la región. Después, la comuna.')
validate(dists, d['Comuna'], f'INDIRECT(VLOOKUP(${region_letter}{FIRST},Mapa_Regiones,2,FALSE))', 'Elige primero la región: la lista muestra solo sus comunas.')
validate(dists, d['Portafolio de productos'], "Productos!$A$6:$A$505", MULTI.format('Lubrax Valora 5W-30, Lubrax Gear 85W-140') + ' Nombres de la hoja Productos.', strict=False)
validate(dists, d['Capacidad logística'], 'Lista_Capacidad', MULTI.format('Stock completo, Línea pesada'), strict=False)
validate(dists, d['Presentaciones que ofrece'], 'Lista_Envases', MULTI.format('20 L, Tambo 200 L'), strict=False)
note(dists, d['Zona'], 'Nombre corto que se muestra en el header («Distribuidor · Zona Talca»). Vacío: «Zona {comuna}».')
note(dists, d['Link google maps'], 'Enlace del lugar en Google Maps. Si incluye las coordenadas (…/@-35.42,-71.65,15z), latitud y longitud se completan solas.')
for h, lo, hi in (('Latitud', -90, 90), ('Longitud', -180, 180)):
    letter = get_column_letter(d[h])
    dv = DataValidation(type='decimal', operator='between', formula1=str(lo), formula2=str(hi), allow_blank=True)
    dv.showErrorMessage = True
    dv.error = 'Número decimal con punto, por ejemplo -35.4264.'
    dv.showInputMessage = True
    dv.promptTitle = h
    dv.prompt = 'Número decimal con punto (Chile: latitud negativa, por ejemplo -35.4264). Opcional si el enlace de Google Maps trae coordenadas.'
    dv.add(f'{letter}{FIRST}:{letter}{LAST}')
    dists.add_data_validation(dv)

# ---------------------------------------------------------------- Instrucciones
ins = wb.create_sheet('Instrucciones', 0)
ins.column_dimensions['A'].width = 120
lines = [
    ('Plantilla de carga de productos y distribuidores · Lubrax', 'title'),
    ('', None),
    ('Cómo se usa', 'h'),
    ('1. Completa las hojas «Productos» y «Distribuidores» desde la fila 6 (una fila por registro).', None),
    ('2. En WordPress: Productos → Importar desde Excel. Usa primero «Simular» para revisar el informe y luego importa.', None),
    ('3. Volver a importar el mismo archivo actualiza los registros (por nombre o SKU); no los duplica. Una celda vacía no borra lo que ya existe.', None),
    ('', None),
    ('Listas desplegables', 'h'),
    ('• Columnas con una sola opción (Categoría, Tipo de producto, Base, Tipo de compra, Región, Comuna, Destacado): elige de la lista; otro valor se rechaza.', None),
    ('• Columnas con varias opciones (Especificaciones, Presentación, Distribuido por, Portafolio, Capacidad, Presentaciones que ofrece): elige una de la lista o escribe varias separadas por coma.', None),
    ('• Comuna depende de la Región: elige primero la región y la lista mostrará solo sus comunas (346 comunas oficiales de Chile).', None),
    ('• «Distribuido por» y «Portafolio de productos» usan los nombres de la otra hoja: escríbelos igual. Basta con completar uno de los dos lados; la relación se crea en ambos.', None),
    ('• Las listas desplegables funcionan en Microsoft Excel y LibreOffice. En Google Sheets la lista dependiente de comunas no está disponible: escribe la comuna tal como aparece en la hoja oculta «Listas».', None),
    ('', None),
    ('Imágenes y documentos', 'h'),
    ('• Pega un enlace público: Google Drive o Dropbox («cualquier persona con el enlace») o una URL web. Imagen: JPG, PNG o WebP. Ficha técnica y SDS: PDF.', None),
    ('• Los archivos se descargan una sola vez; si el enlace ya se importó antes (o es de la biblioteca del sitio), se reutiliza.', None),
    ('', None),
    ('Otras reglas', 'h'),
    ('• No cambies el texto de los encabezados (fila 5): el importador reconoce las columnas por su título. El orden de las columnas no importa.', None),
    ('• Aplicaciones recomendadas: una por línea (Alt + Enter dentro de la celda).', None),
    ('• Propiedades típicas (índice de viscosidad, viscosidad a 100 °C…): escribe el número; la unidad se agrega sola.', None),
    ('• Valores nuevos de viscosidad o especificación se crean al importar. Revisa en el informe de simulación que estén bien escritos.', None),
]
for i, (text, kind) in enumerate(lines, start=1):
    cell = ins.cell(i, 1, text)
    cell.alignment = Alignment(wrap_text=True, vertical='top')
    if kind == 'title':
        cell.font = Font(bold=True, size=16, color='FFFFFF')
        cell.fill = PatternFill('solid', fgColor='032B1A')
        ins.row_dimensions[i].height = 30
    elif kind == 'h':
        cell.font = Font(bold=True, size=12, color=GREEN)

wb.move_sheet('Listas', offset=len(wb.sheetnames) - 1 - wb.sheetnames.index('Listas'))
wb.active = wb.sheetnames.index('Productos')
wb.save(OUT)
print('ok', OUT, len(prod_rows), 'productos', len(dist_rows), 'distribuidores')
