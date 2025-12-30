<?php 
session_start(); 
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoChek | Vehicle Inspection Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Homepage Specific Enhancements */
        .hero {
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.08), transparent),
                        radial-gradient(circle at bottom left, rgba(139, 92, 246, 0.05), transparent),
                        var(--white);
            padding: 120px 0 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero h2 { 
            font-size: 3.5rem; 
            font-weight: 800; 
            letter-spacing: -0.02em; 
            margin-bottom: 24px; 
            line-height: 1.1; 
            color: var(--text-dark);
        }
        .hero h2 span {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p { 
            font-size: 1.25rem; 
            max-width: 700px; 
            margin: 0 auto 48px; 
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Step Card Styling (How It Works) */
        .step-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }
        .step-card {
            text-align: center;
            padding: 30px;
            background: var(--white);
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.3s ease;
        }
        .step-card:hover {
            border-color: var(--primary);
            background: var(--bg-light);
        }
        .step-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 24px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }
        .step-card h3 { font-size: 1.25rem; margin-bottom: 12px; font-weight: 700; }
        .step-card p { color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; }

        /* Expert Cards - Premium Home Version */
        .expert-card-home {
            background: var(--white);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .expert-card-home:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
            border-color: var(--primary);
        }
        .expert-image-container { position: relative; margin-bottom: 20px; }
        .expert-image {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 4px solid var(--white);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #94a3b8;
        }
        .verified-badge {
            position: absolute; bottom: 5px; right: 5px; background: var(--primary); color: white;
            width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            border: 3px solid var(--white); font-size: 14px;
        }
        .expert-card-home h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 8px; color: var(--text-dark); }
        .expert-rating { display: flex; align-items: center; gap: 4px; justify-content: center; font-size: 0.85rem; font-weight: 600; color: #f59e0b; margin-bottom: 12px; }
        .expert-location { font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: 20px; }
        
        .expert-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-size: 2.25rem; font-weight: 800; margin-bottom: 16px; }
        .section-header p { color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

        /* Reviews Styling */
        .review-carousel {
            display: flex; gap: 30px; overflow-x: auto; padding: 40px 0;
            scrollbar-width: none; -ms-overflow-style: none;
        }
        .review-carousel::-webkit-scrollbar { display: none; }
        .review-card {
            min-width: 400px; padding: 32px; border-radius: 24px;
            background: var(--white); border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--card-shadow); transition: all 0.3s ease;
        }
        .review-card:hover { transform: translateY(-5px); box-shadow: var(--hover-shadow); }
        .review-comment { font-size: 1.1rem; color: var(--text-dark); line-height: 1.6; font-style: italic; margin-bottom: 24px; }
        
        /* Benefits Section */
        .benefit-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px; margin-top: 50px;
        }
        .benefit-card {
            background: var(--white); padding: 40px; border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.8); display: flex; flex-direction: column;
            gap: 20px; transition: all 0.3s ease;
        }
        .benefit-card:hover { border-color: var(--primary); background: var(--bg-light); }
        .benefit-icon { font-size: 2.5rem; }
        .benefit-card h4 { font-size: 1.25rem; font-weight: 700; color: var(--text-dark); }
        .benefit-card p { color: var(--text-muted); font-size: 1rem; line-height: 1.6; }

        footer { background: #0f172a; color: white; padding: 100px 0 50px; margin-top: 120px; }
        footer .logo h1 { color: white; font-size: 2rem; margin-bottom: 16px; font-weight: 800; }
        footer .logo span { color: var(--primary); }
        footer p { opacity: 0.6; font-size: 1rem; line-height: 1.6; }
        .footer-link { color: rgba(255,255,255,0.7); text-decoration: none; transition: color 0.2s; }
        .footer-link:hover { color: var(--primary); }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <section class="hero">
        <div class="container">
            <h2>Find <span>Trusted</span> Vehicle <br>Inspection Experts in Badulla</h2>
            <p>Connect with verified professionals for accurate pre-purchase inspections, ensuring a secure and informed buying experience.</p>
            <div style="gap: 20px; display: flex; justify-content: center;">
                <a href="experts.php" class="btn btn-primary" style="padding: 18px 45px; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);">Browse Experts</a>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="register_expert.php" class="btn btn-outline" style="padding: 18px 45px; font-size: 1.1rem; border-radius: 50px;">Join as Expert</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="container" style="padding: 100px 20px;">
        <div class="section-header">
            <h2>Top-Rated Experts</h2>
            <p>Our most experienced professionals ready to assist you in the Badulla district.</p>
        </div>
        <div class="expert-grid">
            <?php
            $top_experts = $conn->query("SELECT * FROM expert WHERE verified = 1 ORDER BY experience DESC LIMIT 4");
            while($exp = $top_experts->fetch_assoc()):
            ?>
            <div class="expert-card-home">
                <div class="expert-image-container">
                    <div class="expert-image">
                        <?php if(!empty($exp['profile_photo'])): ?>
                            <img src="uploads/profiles/<?php echo $exp['profile_photo']; ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($exp['name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <?php if($exp['verified']): ?>
                        <div class="verified-badge" title="Verified Expert">✓</div>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($exp['name']); ?></h3>
                <div class="expert-rating">
                    <span>★ ★ ★ ★ ★</span>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">(5.0)</span>
                </div>
                <div class="expert-meta-small" style="font-weight: 600; color: var(--primary); margin-bottom: 4px;">
                    <?php echo $exp['experience']; ?> Years Experience
                </div>
                <div class="expert-location">
                    📍 <?php echo htmlspecialchars($exp['district']); ?>
                </div>
                <a href="expert_profile.php?id=<?php echo $exp['expert_id']; ?>" class="btn btn-primary" style="width: 100%; border-radius: 12px; padding: 12px;">View Profile</a>
            </div>
            <?php endwhile; ?>
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <a href="experts.php" style="color: var(--primary); font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                View all experts <span>→</span>
            </a>
        </div>
    </section>

    <section style="background: white; padding: 120px 0;">
        <div class="container">
            <div class="section-header">
                <h2>How It Works</h2>
                <p>A simple four-step process to ensure your vehicle purchase is a safe investment.</p>
            </div>
            <div class="step-container">
                <div class="step-card">
                    <div class="step-icon">🔍</div>
                    <h3>Search for Experts</h3>
                    <p>Browse through our verified professionals and find the perfect match for your vehicle type.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">📅</div>
                    <h3>Book Instantly</h3>
                    <p>Select your location and preferred time. Your expert will confirm and reach out to you.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">📄</div>
                    <h3>Technical Report</h3>
                    <p>Receive a comprehensive digital inspection report covering over 100+ critical check points.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">⭐</div>
                    <h3>Leave a Review</h3>
                    <p>Help our community by rating your expert and sharing your inspection experience.</p>
                </div>
            </div>
        </div>
    </section>

    <section style="background: var(--bg-light); padding: 100px 0;">
        <div class="container">
            <div class="section-header">
                <h2>Customer Reviews</h2>
                <p>See why buyers and sellers trust AutoChek for vehicle inspections.</p>
            </div>
            <div class="review-carousel">
                <?php
                $reviews = $conn->query("SELECT r.*, b.name as buyer_name, b.profile_photo as buyer_photo, e.name as expert_name 
                                       FROM review r 
                                       JOIN buyer b ON r.buyer_id = b.buyer_id 
                                       JOIN expert e ON r.expert_id = e.expert_id 
                                       ORDER BY r.review_date DESC LIMIT 5");
                if ($reviews && $reviews->num_rows > 0):
                    while($rev = $reviews->fetch_assoc()):
                ?>
                    <div class="review-card">
                        <div style="color: #f59e0b; margin-bottom:20px; font-size: 1.2rem;">
                            <?php for($i=0; $i<$rev['rating']; $i++) echo "★"; ?>
                            <?php for($i=0; $i<(5-$rev['rating']); $i++) echo "☆"; ?>
                        </div>
                        <p class="review-comment">"<?php echo htmlspecialchars($rev['comment']); ?>"</p>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <div style="width:50px; height:50px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; overflow:hidden; border: 2px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">
                                <?php if(!empty($rev['buyer_photo'])): ?>
                                    <img src="uploads/profiles/<?php echo $rev['buyer_photo']; ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    👤
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size: 1rem;"><?php echo htmlspecialchars($rev['buyer_name']); ?></h4>
                                <small style="color: var(--text-muted);">Inspected by <strong><?php echo htmlspecialchars($rev['expert_name']); ?></strong></small>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile; 
                else:
                    echo "<p style='color:var(--text-muted); text-align: center; width: 100%;'>No reviews yet. Be the first to leave one!</p>";
                endif;
                ?>
            </div>
        </div>
    </section>

    <section style="background: white; padding: 100px 0;">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose AutoChek?</h2>
                <p>The smartest and safest way to buy a used vehicle in the Uva Province.</p>
            </div>
            <div class="benefit-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">🛡️</div>
                    <h4>Verified Experts Only</h4>
                    <p>Every inspector on our platform is personally vetted and verified to ensure professional standard service.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">📊</div>
                    <h4>Comprehensive Reports</h4>
                    <p>Get detailed PDF reports including engine health, body condition, and digital photos of every checklist item.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">⚡</div>
                    <h4>Real-time Booking</h4>
                    <p>Schedule inspections instantly with live availability tracking. Get your results usually within hours of completion.</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 60px; margin-bottom: 60px; text-align: left;">
                <div class="logo">
                    <h1>Auto<span>Chek</span></h1>
                    <p style="max-width: 400px; margin-top: 20px;">The leading marketplace connecting vehicle buyers with trusted inspection experts in Badulla. We make car buying transparent and safe.</p>
                </div>
                <div>
                    <h4 style="color: white; margin-bottom: 24px;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 12px;">
                        <li><a href="experts.php" class="footer-link">Find Experts</a></li>
                        <li><a href="register_expert.php" class="footer-link">Become an Expert</a></li>
                        <li><a href="login.php" class="footer-link">Client Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color: white; margin-bottom: 24px;">Support</h4>
                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                        <p style="font-size: 0.85rem; margin-bottom: 12px; opacity: 1;">Direct Assistance:</p>
                        <a href="tel:+94771234567" style="color: var(--primary); font-weight: 700; font-size: 1.1rem; text-decoration: none; display: block;">📞 +94 77 123 4567</a>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 30px; display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; opacity: 0.5;">
                <div>&copy; <?php echo date('Y'); ?> AutoChek Vehicle Marketplace.</div>
                <div style="display: flex; gap: 24px;">
                    <a href="#" class="footer-link">Privacy Policy</a>
                    <a href="#" class="footer-link">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
