<?php
session_start();
require_once '../config.php';

// Redirect if not seller or not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getDBConnection();
    
    $campus = $conn->real_escape_string($_POST['campus']);
    $diploma = $conn->real_escape_string($_POST['diploma']);
    $semester = (int)$_POST['semester'];
    $cgpa = !empty($_POST['cgpa']) ? (float)$_POST['cgpa'] : NULL;
    $bio = $conn->real_escape_string($_POST['bio']);
    $user_id = (int)$_SESSION['user_id'];

    try {
        // Begin transaction
        $conn->begin_transaction();

        // 1. Insert seller profile
        $stmt = $conn->prepare("INSERT INTO seller_profiles 
                              (user_id, campus, diploma, semester, cgpa, bio) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issids", $user_id, $campus, $diploma, $semester, $cgpa, $bio);
        $stmt->execute();

        // 2. Insert work experiences
        if (!empty($_POST['job_title'])) {
            $stmt = $conn->prepare("INSERT INTO work_experience 
                                  (seller_id, job_title, duration) 
                                  VALUES (?, ?, ?)");
            
            foreach ($_POST['job_title'] as $index => $job_title) {
                if (!empty($job_title) && !empty($_POST['duration'][$index])) {
                    $duration = $conn->real_escape_string($_POST['duration'][$index]);
                    $stmt->bind_param("iss", $user_id, $job_title, $duration);
                    $stmt->execute();
                }
            }
        }

        $conn->commit();
        $success = "Profile completed successfully!";
        header("Refresh: 2; url=dashboard.php");
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complete Your Seller Profile | UniWorkie</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(97, 176, 254, 0.2);
        }
        h1 {
            color: #61B0FE;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        select, input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Segoe UI', sans-serif;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .work-experience {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .add-experience {
            background: #61B0FE;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .remove-experience {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-top: 10px;
        }
        .submit-btn {
            background: #61B0FE;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        .error {
            color: red;
            margin-bottom: 20px;
        }
        .success {
            color: green;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h1>Complete Your Seller Profile</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php else: ?>
        
        <form method="POST" id="profileForm">
            <div class="form-group">
                <label for="campus">Campus</label>
                <select id="campus" name="campus" required>
                    <option value="">Select Campus</option>
                    <option value="Batu Pahat">Batu Pahat</option>
                    <option value="Bangi">Bangi</option>
                    <option value="Ipoh">Ipoh</option>
                    <option value="Kuantan">Kuantan</option>
                    <option value="Alor Setar">Alor Setar</option>
                    <option value="Kota Bharu">Kota Bharu</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="diploma">Diploma Program</label>
                <select id="diploma" name="diploma" required>
                    <option value="">Select Diploma</option>
                    <option value="Diploma Perakaunan">Diploma Perakaunan</option>
                    <option value="Diploma Pengurusan Perniagaan">Diploma Pengurusan Perniagaan</option>
                    <option value="Diploma Pengurusan Sumber Manusia">Diploma Pengurusan Sumber Manusia</option>
                    <option value="Diploma Pengurusan Pelancongan">Diploma Pengurusan Pelancongan</option>
                    <option value="Diploma Pendidikan Awal Kanak-Kanak">Diploma Pendidikan Awal Kanak-Kanak</option>
                    <option value="Diploma Kejururawatan">Diploma Kejururawatan</option>
                    <option value="Diploma TESL">Diploma TESL</option>
                    <option value="Diploma Perbankan dan Kewangan Islam">Diploma Perbankan dan Kewangan Islam</option>
                    <option value="Diploma Teknologi Agro">Diploma Teknologi Agro</option>
                    <option value="Diploma Multimedia">Diploma Multimedia</option>
                    <option value="Diploma Pengurusan dengan Multimedia">Diploma Pengurusan dengan Multimedia</option>
                    <option value="Diploma Teknologi Maklumat">Diploma Teknologi Maklumat</option>
                    <option value="Diploma Pengurusan Sukan">Diploma Pengurusan Sukan</option>
                    <option value="Diploma Sains Komputer">Diploma Sains Komputer</option>
                    <option value="Diploma Pengurusan Pejabat">Diploma Pengurusan Pejabat</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="semester">Current Semester</label>
                <select id="semester" name="semester" required>
                    <option value="">Select Semester</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cgpa">Current CGPA (Optional)</label>
                <input type="number" id="cgpa" name="cgpa" min="0" max="4" step="0.01" placeholder="e.g. 3.45">
            </div>
            
            <div class="form-group">
                <label for="bio">Short Bio (Max 150 characters)</label>
                <textarea id="bio" name="bio" maxlength="150" required></textarea>
            </div>
            
            <h3>Work Experience (Max 3)</h3>
            <div id="workExperienceContainer">
                <!-- Work experiences will be added here dynamically -->
            </div>
            
            <button type="button" class="add-experience" id="addExperience">+ Add Experience</button>
            
            <button type="submit" class="submit-btn">Complete Profile</button>
        </form>
        
        <script>
            let experienceCount = 0;
            const maxExperiences = 3;
            
            document.getElementById('addExperience').addEventListener('click', function() {
                if (experienceCount >= maxExperiences) {
                    alert('Maximum 3 work experiences allowed');
                    return;
                }
                
                experienceCount++;
                
                const container = document.getElementById('workExperienceContainer');
                const div = document.createElement('div');
                div.className = 'work-experience';
                div.innerHTML = `
                    <div class="form-group">
                        <label>Job Title</label>
                        <input type="text" name="job_title[]" required>
                    </div>
                    <div class="form-group">
                        <label>Duration (e.g., 3 months, 2020-2021)</label>
                        <input type="text" name="duration[]" required>
                    </div>
                    <button type="button" class="remove-experience">Remove</button>
                `;
                
                container.appendChild(div);
                
                // Add event listener to remove button
                div.querySelector('.remove-experience').addEventListener('click', function() {
                    container.removeChild(div);
                    experienceCount--;
                });
            });
            
            // Add form validation
            document.getElementById('profileForm').addEventListener('submit', function(e) {
                const cgpa = document.getElementById('cgpa').value;
                if (cgpa && (cgpa < 0 || cgpa > 4)) {
                    alert('CGPA must be between 0 and 4');
                    e.preventDefault();
                }
            });
        </script>
        
        <?php endif; ?>
    </div>
</body>
</html>