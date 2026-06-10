<?php
require_once 'config/config.php';
requireLogin();
requireRole(['student']);
require_once 'includes/icons.php';

require_once 'models/User.php';
require_once 'models/Shop.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$shop = new Shop($db);

$user_id = $_SESSION['user_id'];
$coins = $user->getCoins($user_id);

// Get all shop items
$shop_items = [];
$stmt = $shop->getItems();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $shop_items[] = $row;
}

// Get user inventory
$inventory = [];
$stmt_inv = $shop->getUserInventory($user_id);
while ($row = $stmt_inv->fetch(PDO::FETCH_ASSOC)) {
    $inventory[$row['item_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <title>Shop - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <style>
        .shop-header {
            background: linear-gradient(135deg, 
                rgba(79, 70, 229, 0.2) 0%, 
                rgba(124, 58, 237, 0.15) 50%, 
                rgba(167, 139, 250, 0.1) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .shop-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .shop-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, #fff 0%, #e0e7ff 50%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .shop-header p {
            margin: 0.25rem 0 0 0;
            opacity: 0.8;
            font-size: 0.875rem;
            color: rgba(203, 213, 225, 0.9);
        }

        .coin-balance {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 0.6rem 1rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: bold;
            font-size: 1rem;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .coin-icon {
            color: #fbbf24;
            font-size: 1.1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
        }

        .shop-item {
            background: rgba(30, 30, 55, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .shop-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #8b5cf6, #a78bfa, #8b5cf6);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .shop-item:hover {
            transform: translateY(-5px);
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 12px 35px rgba(139, 92, 246, 0.15);
        }
        
        .shop-item:hover::before {
            opacity: 1;
        }

        .item-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(167, 139, 250, 0.1) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.75rem;
            border: 2px solid rgba(139, 92, 246, 0.2);
            transition: all 0.3s;
        }
        
        .shop-item:hover .item-icon {
            transform: scale(1.1);
            border-color: rgba(139, 92, 246, 0.5);
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.25) 0%, rgba(167, 139, 250, 0.2) 100%);
        }

        .item-name {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #e0e7ff;
        }

        .item-desc {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 1.25rem;
            flex-grow: 1;
            line-height: 1.5;
        }

        .item-cost {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: #fbbf24;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background: rgba(251, 191, 36, 0.1);
            border-radius: 20px;
        }

        .btn-buy {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }

        .btn-buy:hover {
            background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }

        .btn-buy:disabled {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.7;
        }

        .btn-equip {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-equip:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-equip.equipped {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            opacity: 0.85;
        }
        
        .btn-topup {
            margin-left: 12px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-topup:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4);
        }
        
        /* Modal Styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: linear-gradient(145deg, #1a1a2e 0%, #1e1e3a 100%);
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            border: 1px solid rgba(124, 58, 237, 0.3);
            box-shadow: 0 25px 50px rgba(124, 58, 237, 0.25);
        }
        
        .modal-content h2 {
            color: #e0e7ff;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.5rem;
        }
        
        .topup-option {
            background: rgba(45, 45, 68, 0.8);
            padding: 1.25rem;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid transparent;
            transition: all 0.3s;
            margin-bottom: 0.75rem;
        }
        
        .topup-option:hover {
            border-color: #7c3aed;
            background: rgba(54, 54, 82, 0.9);
            transform: translateX(5px);
        }
        
        .topup-coins {
            color: #fbbf24;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }
        
        .topup-price {
            color: #e0e7ff;
            font-weight: 600;
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .shop-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.25rem;
            }
            
            .coin-balance {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .shop-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
            }
            
            .shop-item {
                padding: 1rem;
            }
            
            .item-icon {
                width: 50px;
                height: 50px;
            }
            
            .item-name {
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 480px) {
            .shop-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            
            .shop-item {
                padding: 0.875rem;
            }
            
            .item-icon {
                width: 44px;
                height: 44px;
                font-size: 1.25rem;
            }
            
            .item-name {
                font-size: 0.875rem;
            }
            
            .item-desc {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="page-wrapper" style="margin-top: 80px;">
        <div class="shop-header">
            <div>
                <h1>🛒 Item Shop</h1>
                <p>Tukarkan koinmu dengan item menarik!</p>
            </div>
            <div class="coin-balance">
                <span class="coin-icon">●</span>
                <span id="userCoins"><?php echo number_format($coins); ?></span> Coins
                <button onclick="showTopUpModal()" class="btn-topup">+ Top Up</button>
            </div>
        </div>

        <div class="shop-grid">
            <?php foreach ($shop_items as $item): ?>
                <?php 
                    $owned = isset($inventory[$item['id']]);
                    $equipped = $owned && $inventory[$item['id']]['is_equipped'];
                ?>
                <div class="shop-item">
                    <div class="item-icon">
                        <?php if ($item['type'] == 'title'): ?>
                            <?php icon('crown', 24); ?>
                        <?php elseif ($item['type'] == 'frame'): ?>
                            <?php icon('image', 24); ?>
                        <?php elseif ($item['type'] == 'theme'): ?>
                            <?php icon('paint', 24); ?>
                        <?php else: ?>
                            <?php icon('package', 24); ?>
                        <?php endif; ?>
                    </div>
                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="item-desc"><?php echo htmlspecialchars($item['description']); ?></div>
                    
                    <?php if (!$owned): ?>
                        <div class="item-cost">
                            <span>●</span> <?php echo number_format($item['cost']); ?>
                        </div>
                        <button class="btn-buy" onclick="buyItem(<?php echo $item['id']; ?>, <?php echo $item['cost']; ?>)" 
                                <?php echo ($coins < $item['cost']) ? 'disabled' : ''; ?>>
                            Beli
                        </button>
                    <?php else: ?>
                        <div class="item-cost" style="color: #10b981;">
                            Dimiliki
                        </div>
                        <button class="btn-equip <?php echo $equipped ? 'equipped' : ''; ?>" 
                                onclick="equipItem(<?php echo $item['id']; ?>)">
                            <?php echo $equipped ? 'Dipakai' : 'Pakai'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const csrfToken = "<?php echo generateCsrfToken(); ?>";

        function buyItem(itemId, cost) {
            if (!confirm('Apakah Anda yakin ingin membeli item ini?')) return;

            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('csrf_token', csrfToken);

            fetch('api/shop.php?action=buy', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function equipItem(itemId) {
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('csrf_token', csrfToken);

            fetch('api/shop.php?action=equip', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function showTopUpModal() {
            document.getElementById('topUpModal').style.display = 'flex';
        }

        function processTopUp(amount, price) {
            if (!confirm(`Beli ${amount} Coins seharga Rp ${price.toLocaleString()}?`)) return;
            
            // Simulate Payment Gateway
            const formData = new FormData();
            formData.append('amount', amount);
            formData.append('price', price);
            formData.append('csrf_token', csrfToken);
            
            fetch('api/topup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Pembayaran Berhasil! (Simulasi Payment Gateway)\n' + amount + ' Coins telah ditambahkan ke akun Anda.');
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>

    <!-- Top Up Modal -->
    <div id="topUpModal" class="modal-overlay">
        <div class="modal-content">
            <h2>💰 Top Up Coins</h2>
            <div style="display: grid; gap: 0.75rem;">
                <div class="topup-option" onclick="processTopUp(100, 10000)">
                    <div class="topup-coins"><span style="font-size: 1.3rem;">●</span> 100 Coins</div>
                    <div class="topup-price">Rp 10.000</div>
                </div>
                <div class="topup-option" onclick="processTopUp(500, 45000)">
                    <div class="topup-coins"><span style="font-size: 1.3rem;">●</span> 500 Coins</div>
                    <div class="topup-price">Rp 45.000</div>
                </div>
                <div class="topup-option" onclick="processTopUp(1000, 80000)">
                    <div class="topup-coins"><span style="font-size: 1.3rem;">●</span> 1000 Coins</div>
                    <div class="topup-price">Rp 80.000</div>
                </div>
            </div>
            <button onclick="document.getElementById('topUpModal').style.display='none'" style="margin-top: 1.5rem; width: 100%; padding: 0.875rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">Batal</button>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
