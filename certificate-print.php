<?php
require_once 'config/config.php';
requireLogin();

if (!isset($_GET['course_id'])) {
    header('Location: certificates.php');
    exit;
}

require_once 'models/Course.php';
require_once 'models/Enrollment.php';

$database = new Database();
$db = $database->getConnection();

$course_id = sanitizeInput($_GET['course_id']);
$user_id = $_SESSION['user_id'];

// Verify enrollment and completion
$query = "SELECT c.judul_course, c.kode_course, u.nama_lengkap as student_name, e.completed_at
          FROM enrollments e
          JOIN courses c ON e.course_id = c.id
          JOIN users u ON e.user_id = u.id
          WHERE e.course_id = :course_id AND e.user_id = :user_id AND e.status = 'completed'";

$stmt = $db->prepare($query);
$stmt->bindParam(':course_id', $course_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    die("Sertifikat tidak ditemukan atau kursus belum selesai.");
}

$data = $stmt->fetch(PDO::FETCH_ASSOC);
$date = date('d F Y', strtotime($data['completed_at']));
$certificate_id = "CERT-" . strtoupper(substr(md5($user_id . $course_id . $data['completed_at']), 0, 12));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo htmlspecialchars($data['judul_course']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Great+Vibes&family=Lato:wght@300;400;700&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            background: #f0f0f0;
            font-family: 'Lato', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #333;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }

        #certificate {
            width: 1123px; /* A4 Landscape width in px (approx) */
            height: 794px; /* A4 Landscape height in px (approx) */
            background: #fff;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            padding: 40px;
            box-sizing: border-box;
            overflow: hidden;
        }

        .border-pattern {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px solid #1a1a2e;
            z-index: 1;
        }

        .border-inner {
            position: absolute;
            top: 25px;
            left: 25px;
            right: 25px;
            bottom: 25px;
            border: 1px solid #c0a062;
            z-index: 1;
        }

        .corner {
            position: absolute;
            width: 100px;
            height: 100px;
            z-index: 2;
        }

        .top-left { top: 20px; left: 20px; border-top: 5px solid #1a1a2e; border-left: 5px solid #1a1a2e; }
        .top-right { top: 20px; right: 20px; border-top: 5px solid #1a1a2e; border-right: 5px solid #1a1a2e; }
        .bottom-left { bottom: 20px; left: 20px; border-bottom: 5px solid #1a1a2e; border-left: 5px solid #1a1a2e; }
        .bottom-right { bottom: 20px; right: 20px; border-bottom: 5px solid #1a1a2e; border-right: 5px solid #1a1a2e; }

        .content {
            position: relative;
            z-index: 10;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .logo {
            font-family: 'Cinzel', serif;
            font-size: 24px;
            color: #c0a062;
            margin-bottom: 40px;
            letter-spacing: 4px;
        }

        h1 {
            font-family: 'Cinzel', serif;
            font-size: 60px;
            color: #1a1a2e;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 5px;
        }

        .subtitle {
            font-size: 24px;
            color: #666;
            margin-top: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .presented-to {
            margin-top: 50px;
            font-size: 18px;
            color: #888;
            font-style: italic;
        }

        .student-name {
            font-family: 'Great Vibes', cursive;
            font-size: 80px;
            color: #c0a062;
            margin: 20px 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .course-text {
            font-size: 20px;
            color: #444;
            margin-bottom: 10px;
        }

        .course-name {
            font-size: 32px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 60px;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            width: 80%;
            margin-top: 40px;
        }

        .signature-line {
            width: 250px;
            border-top: 1px solid #333;
            text-align: center;
            padding-top: 10px;
        }

        .signature-name {
            font-family: 'Great Vibes', cursive;
            font-size: 30px;
            color: #1a1a2e;
            margin-bottom: 5px;
        }

        .signature-title {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cert-id {
            position: absolute;
            bottom: 40px;
            font-size: 12px;
            color: #aaa;
            letter-spacing: 1px;
        }

        .seal {
            position: absolute;
            bottom: 60px;
            right: 50%;
            transform: translateX(50%);
            width: 120px;
            height: 120px;
            border: 3px solid #c0a062;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #c0a062;
            font-family: 'Cinzel', serif;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <button onclick="downloadPDF()" class="no-print">Download PDF</button>

    <div id="certificate">
        <div class="border-pattern"></div>
        <div class="border-inner"></div>
        <div class="corner top-left"></div>
        <div class="corner top-right"></div>
        <div class="corner bottom-left"></div>
        <div class="corner bottom-right"></div>

        <div class="content">
            <div class="logo">PROZONE ACADEMY</div>
            
            <h1>Certificate</h1>
            <div class="subtitle">of Completion</div>

            <div class="presented-to">This certificate is proudly presented to</div>
            
            <div class="student-name"><?php echo htmlspecialchars($data['student_name']); ?></div>

            <div class="course-text">For successfully completing the course</div>
            <div class="course-name"><?php echo htmlspecialchars($data['judul_course']); ?></div>

            <div class="footer">
                <div class="signature-block">
                    <div class="signature-name"><?php echo date('d F Y', strtotime($data['completed_at'])); ?></div>
                    <div class="signature-line">
                        <div class="signature-title">Date</div>
                    </div>
                </div>
                
                <div class="signature-block">
                    <div class="signature-name">Prozone CEO</div>
                    <div class="signature-line">
                        <div class="signature-title">Instructor</div>
                    </div>
                </div>
            </div>

            <div class="seal">
                OFFICIAL<br>SEAL
            </div>

            <div class="cert-id">ID: <?php echo $certificate_id; ?></div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('certificate');
            const opt = {
                margin: 0,
                filename: 'Certificate-<?php echo $certificate_id; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };

            // Hide button temporarily
            document.querySelector('.no-print').style.display = 'none';
            
            html2pdf().set(opt).from(element).save().then(() => {
                document.querySelector('.no-print').style.display = 'block';
            });
        }
    </script>
</body>
</html>
