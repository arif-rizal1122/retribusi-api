---
name: The Anatomy of a Claude Prompt
description: Panduan dan kerangka kerja (framework) untuk menyusun prompt yang efektif dan terstruktur saat memberikan instruksi kepada Claude atau AI lainnya, berdasarkan skema HDS.
---

# 🧠 The Anatomy of a Claude prompt

Gunakan kerangka kerja dari skema ini saat Anda perlu menyusun instruksi yang sangat spesifik, panjang, atau kompleks untuk Agent AI (seperti Claude), guna memastikan hasil yang akurat, tepat sasaran, dan minim *hallucination*.

## 1. Task (Tugas)
Jelaskan tujuan utama Anda secara spesifik beserta kriteria keberhasilannya.

```text
I want to [TASK] so that [SUCCESS CRITERIA].
First, read these files completely before responding:
```

## 2. Context Files (File Konteks)
Berikan penjelasan mengenai daftar file referensi yang dilampirkan agar AI memahami fungsi masing-masing file tanpa menebak.

```text
[filename.md] — [what it contains]
[filename.md] — [what it contains]
[filename.md] — [what it contains]
```

## 3. Reference (Referensi & Blueprint)
Tarik benang merah (*reverse-engineer*) apa yang membuat contoh referensi Anda berhasil, lalu jadikan itu sebagai aturan (`rules`).

```text
Here is a reference to what I want to achieve:
[Upload reference file as markdown, or paste it here]

Here's what makes this reference work:
[Paste your reverse-engineered blueprint - the patterns, tone, structure, and rules you extracted from the reference. Format each one as a rule starting with "Always" or "Never."]
```

## 4. Success Brief (Atribut Kriteria Kesuksesan)
Definisikan bentuk teknis/non-teknis dari luaran (*output*) serta apa matriks agar *output* tersebut divalidasi.

```text
Here's what I need for my version:

SUCCESS BRIEF
- Type of output + length: [Contract, memo, report, proposal, landing page, post?]
- Recipient's reaction: [What should they think/feel/do after reading?]
- Does NOT sound like: [What to avoid - generic AI, too casual, formal, jargon-heavy?]
- Success means: [They sign? They approve? They reply? They take action?]
```

## 5. Rules (Aturan Baku)
Batasi ruang gerak imajinasi AI dengan aturan ketat untuk mencegah output melenceng.

```text
My context file contains my standards, constraints, landmines, and audience. Read it fully before starting. If you're about to break one of my rules, stop and tell me.
```

## 6. Conversation (Sesi Tanya Jawab Klarifikatif)
Jangan biarkan AI *langsung bekerja* jika instruksinya abu-abu. Secara eksplisit minta AI bertanya.

```text
DO NOT start executing yet. Instead, ask me clarifying questions (use AskUserQuestion tool or equivalent) so we can refine the approach together step by step.
```

## 7. Plan (Rencana Eksekusi)
Verifikasi pemahaman AI dengan menugaskannya mengulangi 3 aturan terpenting dan merancang langkah sekuensial.

```text
Before you write anything, list the 3 rules from my context file that matter most for this task.
Then give me your execution plan (5 steps maximum).
```

## 8. Alignment (Penyelarasan Tuntas)

```text
Only begin work once we've aligned.
```
