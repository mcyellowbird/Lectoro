<?php
header('Content-Type: application/pdf');
header('Content-Disposition: attachment;filename="report.pdf"');
header('Cache-Control: max-age=0');

// Create the PDF content
$pdf_content = <<<EOD
%PDF-1.4
1 0 obj
<< /Type /Catalog
   /Pages 2 0 R
>>
endobj
2 0 obj
<< /Type /Pages
   /Kids [3 0 R]
   /Count 1
>>
endobj
3 0 obj
<< /Type /Page
   /Parent 2 0 R
   /MediaBox [0 0 612 792]
   /Contents 4 0 R
>>
endobj
4 0 obj
<< /Length 44 >>
stream
BT
/F1 24 Tf
100 700 Td
(Hello, World!) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font
   /Subtype /Type1
   /BaseFont /Helvetica
>>
endobj
trailer
<< /Root 1 0 R >>
%%EOF
EOD;

// Output the PDF content
echo $pdf_content;
?>
