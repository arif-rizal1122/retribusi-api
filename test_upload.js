const fs = require('fs');
const path = require('path');

// Buat gambar dummy transparan 1x1 pixel base64
const dummyBase64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==";
const buffer = Buffer.from(dummyBase64, 'base64');
const tempImgPath = path.join(__dirname, 'dummy.png');
fs.writeFileSync(tempImgPath, buffer);

async function testUpload() {
    const token = '57|jHhSQUaA75F2dJDQM82Gqtw534lgLx2EBuAYhfxsd1720988'; // Ambil token dari request user

    const formData = new FormData();
    const fileBlob = new Blob([buffer], { type: 'image/png' });
    formData.append('image', fileBlob, 'dummy.png');
    formData.append('folder', 'retribusi/test');

    try {
        console.log("Mengirim request ke API...");
        const res = await fetch('http://localhost:8000/api/upload', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });

        const text = await res.text();
        console.log(`Status HTTP: ${res.status}`);
        try {
            const json = JSON.parse(text);
            console.log("Response JSON:", JSON.stringify(json, null, 2));
        } catch (e) {
            console.log("Raw Response:", text);
        }
    } catch (error) {
        console.error("Fetch Network Error:", error);
    } finally {
        fs.unlinkSync(tempImgPath);
    }
}

testUpload();
