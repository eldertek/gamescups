<div class="container mt-4">
  <h1 class="text-center">Nos Actualités</h1>
  <div class="row row-cols-1 row-cols-md-3 g-4 mt-2">
  <?php
  if (isset($connect)) {
    foreach ($connect->query("SELECT * FROM articles") as $row) {
      echo '<div class="col">
              <div class="card">
                <img src="' . $row['banner'] . '" class="card-img-top" alt="...">
                  <div class="card-body">
                    <h5 class="card-title">' . $row["title"] . '</h5>
                    <p class="card-text">' . $row["content"] . '</p>
                  </div>
                </div>
              </div>';
    }
  }
  ?>
  </div>
</div>
