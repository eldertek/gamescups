<div class="container mt-4">
  <h1 class="text-center">Nos Actualités</h1>
  <div class="row row-cols-1 row-cols-md-3 g-4 mt-2">
  <?php
  if (isset($connect) && $connect instanceof PDO) {
      foreach ($connect->query('SELECT title, content, banner FROM articles ORDER BY id DESC') as $row) {
          $banner = gamescups_safe_url(isset($row['banner']) ? (string) $row['banner'] : null, true);
          $title = gamescups_e(isset($row['title']) ? (string) $row['title'] : '');
          $content = nl2br(gamescups_e(isset($row['content']) ? (string) $row['content'] : ''));
          echo '<div class="col">
              <div class="card">';
          if ($banner !== '') {
              echo '<img src="'.gamescups_e($banner).'" class="card-img-top" alt="'.$title.'">';
          }
          echo '  <div class="card-body">
                    <h5 class="card-title">'.$title.'</h5>
                    <p class="card-text">'.$content.'</p>
                  </div>
                </div>
              </div>';
      }
  }
  ?>
  </div>
</div>
