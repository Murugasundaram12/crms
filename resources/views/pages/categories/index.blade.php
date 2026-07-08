@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Categories<span class="badge badge-soft-primary ms-2">{{ $categories->total() }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Categories</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('categories-create')
                <a href="{{ route('categories.create') }}" class="btn btn-primary shadow-sm">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Add Category
                </a>
            @endcan
            @can('categories-edit')
                <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#assignCategoryModal">
                    <i class="ti ti-link me-1"></i>Assign Category
                </button>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <form action="{{ route('categories.index') }}" method="GET" class="row g-3 align-items-end m-0">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Search</label>
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search categories" value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">Main Category</label>
                    <select name="main_category_id" class="form-select">
                        <option value="">All Main Categories</option>
                        @foreach($mainCategories as $mainCategory)
                            <option value="{{ $mainCategory->id }}" @selected(request('main_category_id') == $mainCategory->id)>
                                {{ $mainCategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm">Filter</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Category Modal -->
    <div class="modal fade" id="assignCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Categories to Main Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignCategoryForm" method="POST" action="{{ route('categories.assign') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Main Category <span class="text-danger">*</span></label>
                            <select name="main_category_id" id="modalMainCategory" class="form-select" required>
                                <option value="">Select Main Category</option>
                                @foreach($mainCategories as $mainCategory)
                                    <option value="{{ $mainCategory->id }}">{{ $mainCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categories <span class="text-danger">*</span></label>
                            <input type="text" id="categorySearch" class="form-control mb-2"
                                placeholder="Search categories...">
                            <div class="border rounded" style="max-height: 300px; overflow-y: auto;">
                                <div id="categoryList" class="p-2">
                                    @foreach($masterCategories as $category)
                                        <div class="form-check category-item py-1" data-name="{{ strtolower($category->name) }}"
                                            data-id="{{ $category->id }}">
                                            <input class="form-check-input category-checkbox" type="checkbox"
                                                name="category_ids[]" value="{{ $category->id }}" id="cat{{ $category->id }}"
                                                data-category-id="{{ $category->id }}"
                                                data-category-name="{{ $category->name }}">
                                            <label class="form-check-label" for="cat{{ $category->id }}">
                                                {{ $category->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                    <div id="noCategoriesMsg" class="text-muted text-center py-3 d-none">
                                        No categories found
                                    </div>
                                </div>
                            </div>
                            <div class="form-text mt-2">Checked = Assign to main category. Unchecked = Remove from main
                                category.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-0">
            <div class="table-responsive custom-table">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Main Category</th>
                            <th>Category</th>
                            <th>Created At</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $row)
                            <tr>
                                <td>
                                    {{ $row->main_category_name }}
                                </td>
                                <td>{{ $row->category_name }}</td>
                                <td>{{ $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d M Y') : '-' }}</td>
                                <td class="text-end">
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @can('categories-edit')
                                                <a class="dropdown-item" href="{{ route('categories.edit', $row->category_id) }}"><i
                                                        class="ti ti-edit text-blue"></i> Edit</a>
                                            @endcan
                                            @can('categories-delete')
                                                <button type="button" class="dropdown-item text-danger crm-delete-trigger"
                                                    data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                    data-delete-action="{{ route('categories.destroy', $row->category_id) }}"
                                                    data-delete-title="Delete Category"
                                                    data-delete-message="Are you sure you want to delete category '{{ $row->category_name }}'?">
                                                    <i class="ti ti-trash me-1"></i> Delete
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No assignments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($categories->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $categories->withQueryString()->links() }}
            </div>
        @endif
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const assignedCategories = @json($assignedCategories);
            const masterCategories = @json($masterCategories->toArray());

            const modalMainCategory = document.getElementById('modalMainCategory');
            const categorySearch = document.getElementById('categorySearch');
            const categoryItems = document.querySelectorAll('.category-item');
            const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
            const noCategoriesMsg = document.getElementById('noCategoriesMsg');

            // Handle main category selection - show all categories, check assigned ones
            modalMainCategory.addEventListener('change', function () {
                const selectedMainCatId = this.value;

                // Reset all checkboxes
                categoryCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });

                if (selectedMainCatId && assignedCategories[selectedMainCatId]) {
                    const assignedIds = assignedCategories[selectedMainCatId].map(Number);

                    // Check categories that are already assigned to this main category
                    categoryCheckboxes.forEach(checkbox => {
                        const categoryId = Number(checkbox.dataset.categoryId);
                        if (assignedIds.includes(categoryId)) {
                            checkbox.checked = true;
                        }
                    });
                }

                // Re-apply search filter
                categorySearch.dispatchEvent(new Event('input'));
            });

            // Search functionality - static list, just filter visibility
            categorySearch.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;

                categoryItems.forEach(item => {
                    const categoryName = item.dataset.name;
                    const isVisible = (searchTerm === '' || categoryName.includes(searchTerm));
                    item.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleCount++;
                });

                noCategoriesMsg.classList.toggle('d-none', visibleCount > 0);
            });

            // Reset form when modal is closed
            $('#assignCategoryModal').on('hidden.bs.modal', function () {
                $('#assignCategoryForm')[0].reset();
                categoryCheckboxes.forEach(checkbox => checkbox.checked = false);
                categoryItems.forEach(item => item.style.display = '');
                categorySearch.value = '';
                noCategoriesMsg.classList.add('d-none');
            });
        });
    </script>
@endpush
