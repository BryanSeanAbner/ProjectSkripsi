"""
Konversi dokumentasi_sistem.md → HTML yang siap di-print ke PDF.
Buka file HTML di browser lalu Ctrl+P → Save as PDF.
"""
import os
import markdown

SRC = r"C:\Users\Asus\.gemini\antigravity\brain\1945121a-4137-4992-a285-9ecb7d27dd4a\dokumentasi_sistem.md"
DST = r"c:\laragon\www\ProjectTA\Dokumentasi_Sistem_Rekomendasi.html"

with open(SRC, "r", encoding="utf-8") as f:
    md_text = f.read()

html_body = markdown.markdown(
    md_text,
    extensions=["tables", "fenced_code", "toc", "attr_list"]
)

html_full = f"""<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dokumentasi Sistem Rekomendasi Berita — ProjectTA</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
  
  * {{ margin: 0; padding: 0; box-sizing: border-box; }}
  
  body {{
    font-family: 'Inter', -apple-system, sans-serif;
    line-height: 1.7;
    color: #1a1a2e;
    background: #fff;
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 50px;
    font-size: 14px;
  }}
  
  h1 {{
    font-size: 28px;
    font-weight: 800;
    color: #0f0f23;
    margin: 40px 0 15px;
    padding-bottom: 10px;
    border-bottom: 3px solid #e53935;
  }}
  
  h2 {{
    font-size: 22px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 35px 0 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #eee;
  }}
  
  h3 {{
    font-size: 17px;
    font-weight: 700;
    color: #333;
    margin: 25px 0 10px;
  }}
  
  h4 {{
    font-size: 15px;
    font-weight: 600;
    color: #555;
    margin: 20px 0 8px;
  }}
  
  p {{
    margin: 10px 0;
  }}
  
  table {{
    border-collapse: collapse;
    width: 100%;
    margin: 15px 0;
    font-size: 13px;
  }}
  
  th {{
    background: #1a1a2e;
    color: #fff;
    padding: 10px 14px;
    text-align: left;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }}
  
  td {{
    padding: 9px 14px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
  }}
  
  tr:nth-child(even) {{ background: #f8f9fa; }}
  tr:hover {{ background: #f0f4ff; }}
  
  code {{
    background: #f1f3f5;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 13px;
    font-family: 'Consolas', monospace;
    color: #c0392b;
  }}
  
  pre {{
    background: #1e1e2e;
    color: #cdd6f4;
    padding: 18px 20px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 15px 0;
    font-size: 13px;
    line-height: 1.5;
  }}
  
  pre code {{
    background: none;
    padding: 0;
    color: inherit;
  }}
  
  blockquote {{
    border-left: 4px solid #e53935;
    padding: 12px 20px;
    margin: 15px 0;
    background: #fff8f8;
    border-radius: 0 8px 8px 0;
    font-size: 13px;
  }}
  
  blockquote strong {{
    color: #e53935;
  }}

  ul, ol {{
    padding-left: 25px;
    margin: 10px 0;
  }}
  
  li {{
    margin: 5px 0;
  }}
  
  hr {{
    border: none;
    border-top: 2px solid #eee;
    margin: 30px 0;
  }}
  
  a {{
    color: #1a73e8;
    text-decoration: none;
  }}
  
  /* Mermaid diagrams won't render in static HTML, show as code */
  .language-mermaid {{
    background: #f8f9fa;
    color: #333;
    border: 1px dashed #ccc;
    font-style: italic;
  }}

  @media print {{
    body {{ padding: 20px 30px; font-size: 12px; }}
    h1 {{ font-size: 24px; page-break-after: avoid; }}
    h2 {{ font-size: 18px; page-break-after: avoid; }}
    h3 {{ page-break-after: avoid; }}
    table {{ page-break-inside: avoid; font-size: 11px; }}
    pre {{ page-break-inside: avoid; font-size: 11px; }}
    blockquote {{ page-break-inside: avoid; }}
  }}
</style>
</head>
<body>
{html_body}
</body>
</html>"""

with open(DST, "w", encoding="utf-8") as f:
    f.write(html_full)

print(f"[OK] HTML berhasil dibuat: {DST}")
print("     Buka file di browser → Ctrl+P → Save as PDF")
