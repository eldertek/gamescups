<div class="container mt-4 mb-4">
    <h1 class="text-center">Notre galerie photos</h1>
    <div class="row row-cols-1 row-cols-md-3 g-4 mt-2">
        <?php
        if (isset($connect) && $connect instanceof PDO) {
            foreach ($connect->query('SELECT title, image FROM photos ORDER BY id DESC') as $row) {
                $image = gamescups_safe_url(isset($row['image']) ? (string) $row['image'] : null, true);
                $title = gamescups_e(isset($row['title']) ? (string) $row['title'] : '');
                if ($image === '') {
                    continue;
                }
                echo '
                    <div class="col">
                        <div class="card">
                            <img src="'.gamescups_e($image).'" class="card-img-top" alt="'.$title.'">
                            <div class="card-body">
                                <h5 class="card-title">'.$title.'</h5>
                            </div>
                        </div>
                    </div>';
            }
        }
        ?>
    </div>
</div>
