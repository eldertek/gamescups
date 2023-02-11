<div class="container mt-4 mb-4">
    <h1 class="text-center">Notre gallerie photos</h1>
    <div class="row row-cols-1 row-cols-md-3 g-4 mt-2">
        <?php
        if (isset($connect)) {
            foreach ($connect->query("SELECT * FROM photos") as $row) {
                echo '
                    <div class="col">
                        <div class="card">
                            <img src="'. $row["image"] .'" class="card-img-top" alt="'. $row["title"] .'">
                            <div class="card-body">
                                <h5 class="card-title">'. $row["title"] .'</h5>
                            </div>
                        </div>
                    </div>';
            }
        }
        ?>
    </div>
</div>