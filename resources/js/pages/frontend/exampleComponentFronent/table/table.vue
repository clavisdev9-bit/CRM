<template>
<FrontendLayouts>

<div class="container-xxl flex-grow-1 container-p-y">
   <h4 class="fw-bold py-3"><span class="text-muted fw-light"></span>Table Template Layout</h4>
      <!-- CARD FILTERS -->
      <div class="card mb-4 shadow-sm">
        <div class="card-header border-bottom py-3">
          <h5 class="card-title mb-0 fw-semibold">Search Filters</h5>
        </div>

        <div class="card-body">

          <!-- TOP FILTER -->
          <div class="row g-3 mb-4 mt-3">

            <div class="col-md-4">
              <select class="form-select form-select-sm">
                <option>Select Role</option>
                <option>Admin</option>
                <option>User</option>
              </select>
            </div>

            <div class="col-md-4">
              <select class="form-select form-select-sm">
                <option>Select Plan</option>
                <option>Basic</option>
                <option>Premium</option>
              </select>
            </div>

            <div class="col-md-4">
              <select class="form-select form-select-sm">
                <option>Select Status</option>
                <option>Active</option>
                <option>Inactive</option>
              </select>
            </div>

          </div>

          <!-- TOOLBAR BOTTOM -->
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">

            <!-- Show per page -->
            <select class="form-select form-select-sm" style="width: 80px;">
              <option>10</option>
              <option>25</option>
              <option>50</option>
            </select>

            <!-- Right tools -->
            <div class="d-flex flex-wrap gap-2 align-items-center">

              <!-- Search -->
              <div class="input-group input-group-sm" style="width: 220px;">
                <input type="text" class="form-control" placeholder="Search User">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
              </div>

              <!-- Export dropdown -->
              <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fa fa-download"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="#">Export PDF</a></li>
                  <li><a class="dropdown-item" href="#">Export Excel</a></li>
                  <li><a class="dropdown-item" href="#">Export CSV</a></li>
                </ul>
              </div>

            <div class="dropdown"> <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" > 
                <i class="fa fa-filter"></i> Filter </button> 
                <ul class="dropdown-menu dropdown-menu-end p-2">
                     <li class="mb-2"> <small class="text-muted">Status</small> 
                        <select class="form-control form-control-sm"> 
                            <option>Semua</option> 
                            <option>Active</option> 
                            <option>Inactive</option> 
                        </select>
                    </li> 
                    <li class="mb-2">
                            <small class="text-muted">Kategori</small>
                            <select class="form-control form-control-sm"> 
                            <option>Semua Kategori</option> 
                            </select>
                    </li> 
                        <button class="btn btn-primary btn-sm w-100 mt-2"> Terapkan </button> 
                </ul> 
            </div>

              <!-- Add User -->
              <button class="btn btn-primary btn-sm">
                <i class="fa fa-plus me-1"></i> Add New User
              </button>

            </div>
          </div>

        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="card shadow-sm">
        <div class="card-body">

          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nama Product</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Status</th>
                  <th>Deskripsi</th>
                  <th>Created At</th>
                  <th>Updated At</th>
                  <th class="handle">Handle</th>
                </tr>
              </thead>

              <tbody>

                <tr v-for="(p,i) in products" :key="i">
                  <td>{{ p.id }}</td>
                  <td>{{ p.name }}</td>
                  <td>{{ p.category }}</td>
                  <td>{{ p.price }}</td>
                  <td>{{ p.stock }}</td>
                  <td>{{ p.status }}</td>
                  <td>{{ p.desc }}</td>
                  <td>{{ p.created }}</td>
                  <td>{{ p.updated }}</td>

                  <td>
                    <button class="btn btn-sm btn-outline-warning me-2">
                      <i class="fa fa-edit"></i>
                    </button>

                    <button class="btn btn-sm btn-outline-danger">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>

              </tbody>
            </table>
          </div>

          <!-- PAGINATION -->
          <div class="mt-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <button class="btn btn-danger btn-sm">
              <i class="fa fa-chevron-left"></i> Previous
            </button>

            <span class="text-muted">Page 1 of 10</span>

            <button class="btn btn-primary btn-sm">
              Next <i class="fa fa-chevron-right"></i>
            </button>
          </div>

          <p class="text-muted text-center mt-2">
            Menampilkan 10 data pada halaman 1 dari total 100 data.
          </p>

        </div>
      </div>

    </div>
</FrontendLayouts>
</template>
<script setup>
import { onMounted } from "vue";
import FrontendLayouts from '../../../../layouts/frontendLayouts.vue'
const products = [
  { id:1, name:"Contoh Product A", category:"Kategori A", price:"Rp 120.000", stock:40, status:"Active", desc:"Produk A deskripsi singkat", created:"2025-01-01", updated:"2025-01-03" },
  { id:2, name:"Contoh Product B", category:"Kategori B", price:"Rp 85.000", stock:75, status:"Inactive", desc:"Produk B deskripsi singkat", created:"2025-01-02", updated:"2025-01-05" },
  { id:3, name:"Contoh Product C", category:"Kategori A", price:"Rp 150.000", stock:22, status:"Active", desc:"Produk C deskripsi singkat", created:"2025-01-03", updated:"2025-01-06" },
  { id:4, name:"Contoh Product D", category:"Kategori C", price:"Rp 60.000", stock:150, status:"Active", desc:"Produk D deskripsi singkat", created:"2025-01-04", updated:"2025-01-07" },
  { id:5, name:"Contoh Product E", category:"Kategori B", price:"Rp 210.000", stock:10, status:"Inactive", desc:"Produk E deskripsi singkat", created:"2025-01-05", updated:"2025-01-08" },
  { id:6, name:"Contoh Product F", category:"Kategori A", price:"Rp 99.000", stock:200, status:"Active", desc:"Produk F deskripsi singkat", created:"2025-01-06", updated:"2025-01-09" },
  { id:7, name:"Contoh Product G", category:"Kategori C", price:"Rp 180.000", stock:18, status:"Inactive", desc:"Produk G deskripsi singkat", created:"2025-01-07", updated:"2025-01-11" },
  { id:8, name:"Contoh Product H", category:"Kategori A", price:"Rp 75.000", stock:64, status:"Active", desc:"Produk H deskripsi singkat", created:"2025-01-08", updated:"2025-01-12" },
  { id:9, name:"Contoh Product I", category:"Kategori B", price:"Rp 300.000", stock:5, status:"Active", desc:"Produk I deskripsi singkat", created:"2025-01-09", updated:"2025-01-13" },
  { id:10, name:"Contoh Product J", category:"Kategori C", price:"Rp 140.000", stock:90, status:"Active", desc:"Produk J deskripsi singkat", created:"2025-01-10", updated:"2025-01-14" },
];
</script>


<style scoped>
.handle {
  width: 6%;
}

.input-group-text {
  background: #f4f5f7;
}

.btn-primary.btn-sm {
  background-color: #696cff;
  border-color: #696cff;
}
</style>