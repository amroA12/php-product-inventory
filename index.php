<?php
// بداية الملف: لا تضع أي مخرجات قبل session_start()
session_start(); // بدء الجلسة لاستخدام $_SESSION

// -------------------- تهيئة البيانات (تُنفّذ مرة واحدة لكل جلسة) --------------------
// إذا لم تكن هناك منتجات مخزنة في الجلسة نُنشئ عينتين افتراضيتين
if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [
        [
            "id" => 1,
            "name" => "Laptop",
            "description" => "A powerful gaming laptop",
            "price" => 1500.00,
            "category" => "Electronics"
        ],
        [
            "id" => 2,
            "name" => "Chair",
            "description" => "Comfortable office chair",
            "price" => 200.00,
            "category" => "Furniture"
        ]
    ];
}

// نستخدم مرجع محلي للمنتجات ليستهل العرض والتعديل
$products = $_SESSION['products'];

// قائمة التصنيفات لا تتغير هنا (يمكن تعديلها بسهولة)
$categories = ["Electronics", "Furniture", "Clothing", "Books", "Other"];

// مصفوفة لتخزين الأخطاء وبيانات المُدخلات
$errors = [];
$submittedData = [
    "name" => "",
    "description" => "",
    "price" => "",
    "category" => ""
];

// رسالة النجاح (تُقرأ من الجلسة بعد إعادة التوجيه ثم تُحذف)
$successMessage = "";
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']); // نجعل الرسالة تظهر لمرة واحدة فقط
}

// -------------------- معالجة النموذج عند POST --------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // تنظيف المُدخلات الأساسية (trim)
    $submittedData["name"] = isset($_POST["name"]) ? trim($_POST["name"]) : "";
    $submittedData["description"] = isset($_POST["description"]) ? trim($_POST["description"]) : "";
    $submittedData["price"] = isset($_POST["price"]) ? trim($_POST["price"]) : "";
    $submittedData["category"] = isset($_POST["category"]) ? trim($_POST["category"]) : "";

    // التحققات (Validation)
    if ($submittedData["name"] === "") {
        $errors["name"] = "يجب إدخال اسم المنتج.";
    }
    if ($submittedData["description"] === "") {
        $errors["description"] = "يجب إدخال وصف المنتج.";
    }
    // السعر يجب أن يكون رقم موجب
    if ($submittedData["price"] === "") {
        $errors["price"] = "يجب إدخال السعر.";
    } elseif (!is_numeric($submittedData["price"])) {
        $errors["price"] = "السعر يجب أن يكون رقماً.";
    } elseif (floatval($submittedData["price"]) <= 0) {
        $errors["price"] = "السعر يجب أن يكون أكبر من صفر.";
    }
    if ($submittedData["category"] === "") {
        $errors["category"] = "يجب اختيار تصنيف.";
    }

    // إذا لم توجد أخطاء → أضف المنتج
    if (empty($errors)) {
        // توليد id فريد (أخذ أكبر id حالي وإضافة 1)
        $existingIds = array_column($products, 'id');
        $newId = empty($existingIds) ? 1 : (max($existingIds) + 1);

        // إنشاء المنتج الجديد (نخزن القيم الخام؛ نطبّق htmlspecialchars عند العرض فقط)
        $newProduct = [
            "id" => $newId,
            "name" => $submittedData["name"],
            "description" => $submittedData["description"],
            "price" => floatval($submittedData["price"]),
            "category" => $submittedData["category"]
        ];

        // نضيف المنتج للمصفوفة ونقوم بتحديث الجلسة حتى تبقى الإضافة محفوظة
        $products[] = $newProduct;
        $_SESSION['products'] = $products; // تحديث الجلسة

        // رسالة نجاح تُحفظ في الجلسة لتظهر مرة واحدة بعد إعادة التوجيه
        $_SESSION['success'] = "تمت إضافة المنتج بنجاح ✔";

        // نستخدم نمط Post-Redirect-Get: إعادة توجيه لمنع إعادة الإرسال وحفظ الرسالة للعرض مرة واحدة
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    // إن وُجدت أخطاء: لا نعيد التوجيه، ونبقي $submittedData و $errors لعرضها في النموذج
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="utf-8">
    <title>نظام إدارة المنتجات</title>
    <!-- Bootstrap CDN -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

    <h1 class="mb-4 text-center">نظام إدارة المنتجات</h1>

    <!-- عرض رسالة النجاح لمرة واحدة -->
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <!-- عرض رسالة خطأ عامة إذا وُجدت أخطاء -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">يوجد أخطاء في الإدخال، يرجى تصحيح الحقول المحددة أدناه.</div>
    <?php endif; ?>

    <!-- جدول عرض المنتجات -->
    <div class="card mb-4">
        <div class="card-header">قائمة المنتجات</div>
        <div class="card-body">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                <tr>
                    <th style="width:60px">#</th>
                    <th>الاسم</th>
                    <th>الوصف</th>
                    <th style="width:120px">السعر ($)</th>
                    <th style="width:140px">التصنيف</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= (int)$product["id"] ?></td>
                            <td><?= htmlspecialchars($product["name"]) ?></td>
                            <td><?= htmlspecialchars($product["description"]) ?></td>
                            <td><?= number_format((float)$product["price"], 2) ?></td>
                            <td><?= htmlspecialchars($product["category"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">لا توجد منتجات حتى الآن.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- نموذج إضافة منتج جديد -->
    <div class="card">
        <div class="card-header">إضافة منتج جديد</div>
        <div class="card-body">
            <form method="POST" action="">
                <!-- حقل الاسم -->
                <div class="mb-3">
                    <label class="form-label">اسم المنتج</label>
                    <input type="text" name="name"
                           class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($submittedData['name']) ?>">
                    <div class="invalid-feedback"><?= $errors['name'] ?? '' ?></div>
                </div>

                <!-- حقل الوصف -->
                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description"
                              class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                              rows="3"><?= htmlspecialchars($submittedData['description']) ?></textarea>
                    <div class="invalid-feedback"><?= $errors['description'] ?? '' ?></div>
                </div>

                <!-- حقل السعر -->
                <div class="mb-3">
                    <label class="form-label">السعر ($)</label>
                    <input type="text" name="price"
                           class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($submittedData['price']) ?>">
                    <div class="invalid-feedback"><?= $errors['price'] ?? '' ?></div>
                </div>

                <!-- حقل التصنيف -->
                <div class="mb-3">
                    <label class="form-label">التصنيف</label>
                    <select name="category" class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>">
                        <option value="">-- اختر تصنيف --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $submittedData['category'] === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?= $errors['category'] ?? '' ?></div>
                </div>

                <button type="submit" class="btn btn-primary">إضافة المنتج</button>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
