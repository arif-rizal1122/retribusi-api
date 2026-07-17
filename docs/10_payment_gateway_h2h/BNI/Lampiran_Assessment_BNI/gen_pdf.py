from reportlab.lib.pagesizes import A4
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Image as RLImage
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch
from PIL import Image

def build_pdf():
    doc = SimpleDocTemplate("Topologi_dan_Arsitektur.pdf", pagesize=A4, rightMargin=40, leftMargin=40, topMargin=40, bottomMargin=40)
    styles = getSampleStyleSheet()
    
    title_style = styles['Heading1']
    title_style.alignment = 1 # Center
    
    h2_style = styles['Heading2']
    h2_style.spaceAfter = 6
    
    body_style = styles['Normal']
    body_style.spaceAfter = 4
    
    story = []
    
    story.append(Paragraph("Topologi & Arsitektur Jaringan (M-PAD Bapenda)", title_style))
    story.append(Spacer(1, 0.2 * inch))
    
    story.append(Paragraph("Arsitektur Aplikasi", h2_style))
    story.append(Paragraph("Aplikasi M-PAD (Pajak Daerah) dibangun menggunakan arsitektur <b>Monolithic (MVC)</b>.", body_style))
    story.append(Paragraph("Backend: PHP Laravel 11.", body_style))
    story.append(Paragraph("Database: PostgreSQL / MySQL.", body_style))
    story.append(Paragraph("Web Server: Nginx.", body_style))
    story.append(Spacer(1, 0.2 * inch))
    
    story.append(Paragraph("Topologi Jaringan", h2_style))
    story.append(Spacer(1, 0.1 * inch))
    
    img_path = "Topologi_dan_Arsitektur.png"
    img = Image.open(img_path).convert("RGBA")
    bg = Image.new("RGB", img.size, (255, 255, 255))
    bg.paste(img, mask=img.split()[3])
    bg.save("Topologi_dan_Arsitektur_white.png")
    
    a4_width, a4_height = A4
    avail_width = a4_width - 80
    avail_height = a4_height - 300 
    
    im = RLImage("Topologi_dan_Arsitektur_white.png")
    
    aspect = im.drawHeight / im.drawWidth
    if im.drawWidth > avail_width:
        im.drawWidth = avail_width
        im.drawHeight = avail_width * aspect
        
    if im.drawHeight > avail_height:
        im.drawHeight = avail_height
        im.drawWidth = avail_height / aspect
        
    story.append(im)
    doc.build(story)

build_pdf()
