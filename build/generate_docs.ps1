# Generates build/erd.pdf, build/algorithm.pdf, build/report.docx

$buildDir = Split-Path -Parent $MyInvocation.MyCommand.Path

function Write-AsciiPdf {
    param([string]$Path, [string[]]$Lines)
    $y = 800
    $stream = ""
    foreach ($line in $Lines) {
        $safe = ($line -replace '\\', '\\\\' -replace '\(', '\\(' -replace '\)', '\\)')
        $stream += "BT /F1 11 Tf 50 $y Td ($safe) Tj ET`n"
        $y -= 16
    }
    $len = $stream.Length
    $pdf = @"
%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj
4 0 obj<</Length $len>>stream
$stream
endstream
endobj
5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000266 00000 n 
trailer<</Size 6/Root 1 0 R>>
startxref
400
%%EOF
"@
    [System.IO.File]::WriteAllText($Path, $pdf.Trim())
}

$erdLines = @(
    "ER Diagram - mirigrushek (3NF)",
    "Roles 1---* Users",
    "Users 1---* Orders",
    "OrderStatuses 1---* Orders",
    "PickupPoints 1---* Orders",
    "Products 1---* OrderItems",
    "Orders 1---* OrderItems",
    "Categories 1---* Products",
    "Suppliers 1---* Products",
    "Manufacturers 1---* Products",
    "Units 1---* Products"
)
Write-AsciiPdf (Join-Path $buildDir "erd.pdf") $erdLines

$algoLines = @(
    "Algorithm - Mirigrushek IS (GOST 19.701-90)",
    "START -> Login page",
    "Guest -> catalog without filters",
    "Auth OK -> load role from DB",
    "Manager/Admin -> search filter sort",
    "Admin -> product and order CRUD",
    "Show products with row highlighting",
    "END"
)
Write-AsciiPdf (Join-Path $buildDir "algorithm.pdf") $algoLines

$temp = Join-Path $env:TEMP "mirigrushek_docx_$(Get-Random)"
New-Item -ItemType Directory -Force -Path "$temp\word\_rels" | Out-Null
New-Item -ItemType Directory -Force -Path "$temp\_rels" | Out-Null

@'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
'@ | Out-File -FilePath "$temp\[Content_Types].xml" -Encoding utf8

@'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
'@ | Out-File -FilePath "$temp\_rels\.rels" -Encoding utf8

@'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>
'@ | Out-File -FilePath "$temp\word\_rels\document.xml.rels" -Encoding utf8

@'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body>
<w:p><w:r><w:t>Отчёт об отладке ИС «МирИгрушек»</w:t></w:r></w:p>
<w:p><w:r><w:t>1. Окно входа: авторизация по логину и паролю из БД.</w:t></w:r></w:p>
<w:p><w:r><w:t>2. Гость: просмотр каталога без фильтрации.</w:t></w:r></w:p>
<w:p><w:r><w:t>3. Клиент: каталог товаров, ФИО в шапке.</w:t></w:r></w:p>
<w:p><w:r><w:t>4. Менеджер: поиск, фильтр, сортировка, заказы.</w:t></w:r></w:p>
<w:p><w:r><w:t>5. Администратор: CRUD товаров и заказов.</w:t></w:r></w:p>
<w:p><w:r><w:t>6. Подсветка строк по критериям задания.</w:t></w:r></w:p>
</w:body>
</w:document>
'@ | Out-File -FilePath "$temp\word\document.xml" -Encoding utf8

$zipPath = Join-Path $buildDir "report.zip"
$docxPath = Join-Path $buildDir "report.docx"
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
if (Test-Path $docxPath) { Remove-Item $docxPath -Force }
Compress-Archive -Path "$temp\*" -DestinationPath $zipPath -Force
Move-Item $zipPath $docxPath -Force
Remove-Item $temp -Recurse -Force
Write-Host "Done."
