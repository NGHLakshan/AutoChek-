<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expert Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .close-modal {
            color: #94a3b8;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }
        .close-modal:hover { color: #64748b; }
        .detail-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
        }
        .detail-value {
            color: #1e293b;
        }
    </style>
</head>
<body>

<!-- Expert Details Modal -->
<div id="expertModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeExpertModal()">&times;</span>
        <h2 style="margin-top: 0;">Expert Details</h2>
        <div id="expertDetailsContent">
            <p style="text-align: center; color: #94a3b8; padding: 40px;">Loading...</p>
        </div>
    </div>
</div>

<script>
function viewExpertDetails(expertId) {
    document.getElementById('expertModal').style.display = 'block';
    document.getElementById('expertDetailsContent').innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 40px;">Loading...</p>';
    
    // Fetch expert details via AJAX
    fetch('get_expert_details.php?id=' + expertId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const expert = data.expert;
                let html = '';
                
                // Profile Photo
                if (expert.profile_photo) {
                    html += `<div style="text-align: center; margin-bottom: 30px;">
                        <img src="uploads/profiles/${expert.profile_photo}" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #2563eb;">
                    </div>`;
                }
                
                // Details
                html += `
                    <div class="detail-row">
                        <div class="detail-label">Name:</div>
                        <div class="detail-value">${expert.name}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value">${expert.email}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value">${expert.phone}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">District:</div>
                        <div class="detail-value">${expert.district}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Experience:</div>
                        <div class="detail-value">${expert.experience} Years</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Qualification:</div>
                        <div class="detail-value">${expert.qualification}</div>
                    </div>
                `;
                
                if (expert.bio) {
                    html += `
                        <div class="detail-row">
                            <div class="detail-label">Bio:</div>
                            <div class="detail-value">${expert.bio.replace(/\n/g, '<br>')}</div>
                        </div>
                    `;
                }
                
                if (expert.linkedin_url) {
                    html += `
                        <div class="detail-row">
                            <div class="detail-label">LinkedIn:</div>
                            <div class="detail-value"><a href="${expert.linkedin_url}" target="_blank" style="color: #0077b5;">View Profile</a></div>
                        </div>
                    `;
                }
                
                if (expert.website_url) {
                    html += `
                        <div class="detail-row">
                            <div class="detail-label">Website:</div>
                            <div class="detail-value"><a href="${expert.website_url}" target="_blank" style="color: #2563eb;">Visit Website</a></div>
                        </div>
                    `;
                }
                
                if (expert.specializations && expert.specializations.length > 0) {
                    html += `
                        <div class="detail-row">
                            <div class="detail-label">Specializations:</div>
                            <div class="detail-value">
                                ${expert.specializations.map(spec => `<span style="background: #eff6ff; color: #1d4ed8; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; margin: 0 5px 5px 0; display: inline-block;">${spec}</span>`).join('')}
                            </div>
                        </div>
                    `;
                }
                
                html += `
                    <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end;">
                        <button onclick="closeExpertModal()" class="btn btn-outline">Close</button>
                        <a href="admin_approve.php?id=${expert.expert_id}" class="btn btn-primary">Approve Expert</a>
                    </div>
                `;
                
                document.getElementById('expertDetailsContent').innerHTML = html;
            } else {
                document.getElementById('expertDetailsContent').innerHTML = '<p style="text-align: center; color: #ef4444; padding: 40px;">Error loading expert details.</p>';
            }
        })
        .catch(error => {
            document.getElementById('expertDetailsContent').innerHTML = '<p style="text-align: center; color: #ef4444; padding: 40px;">Error loading expert details.</p>';
        });
}

function closeExpertModal() {
    document.getElementById('expertModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('expertModal');
    if (event.target == modal) {
        closeExpertModal();
    }
}
</script>

</body>
</html>
