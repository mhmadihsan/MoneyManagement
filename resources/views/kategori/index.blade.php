<x-bootstrap-layout>
    <div class="card card-custom shadow-sm">
        <div class="card-header card-header-custom">
            <h4 class="fw-bold text-blue mb-0"><i class="bi bi-tags me-2"></i>Master Kategori</h4>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('kategori.store') }}" method="POST" class="mb-5 bg-light p-4 rounded-3 border">
                @csrf
                <div class="row align-items-end g-3">
                    <div class="col-md-9">
                        <label for="nama" class="form-label fw-semibold text-secondary">Nama Kategori Baru</label>
                        <input type="text" id="nama" name="nama" class="form-control form-control-lg border-primary-subtle" placeholder="Contoh: Gaji, Makan, Transportasi" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                            <i class="bi bi-plus-lg me-2"></i>Tambah
                        </button>
                    </div>
                </div>
                @error('nama')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </form>

            <form action="{{ route('kategori.index') }}" method="GET" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari kategori...">
                            <button type="submit" class="btn btn-primary fw-bold px-4">Cari</button>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        @if($search)
                            <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>Reset
                            </a>
                        @endif
                        <span class="text-muted small ms-2">
                            Menampilkan {{ $kategoris->firstItem() ?? 0 }}&ndash;{{ $kategoris->lastItem() ?? 0 }} dari {{ $kategoris->total() }} data
                        </span>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-primary text-white">
                        <tr>
                            <th class="px-4 py-3 border-0" style="border-radius: 10px 0 0 0">No</th>
                            <th class="px-4 py-3 border-0">Nama Kategori</th>
                            <th class="px-4 py-3 border-0 text-center" style="border-radius: 0 10px 0 0">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kategoris as $k)
                            <tr>
                                <td class="px-4 py-3">{{ $kategoris->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-3 fw-medium">{{ $k->nama }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary px-3" data-bs-toggle="modal" data-bs-target="#editKategoriModal{{ $k->id }}">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>

                                        <form action="{{ route('kategori.destroy', $k) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                                <i class="bi bi-trash me-1"></i>Hapus
                                            </button>
                                        </form>
                                    </div>

                                    <div class="modal fade text-start" id="editKategoriModal{{ $k->id }}" tabindex="-1" aria-labelledby="editKategoriModalLabel{{ $k->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('kategori.update', $k) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editKategoriModalLabel{{ $k->id }}">
                                                            <i class="bi bi-pencil-square me-2"></i>Edit Kategori
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <label for="nama-kategori-{{ $k->id }}" class="form-label fw-semibold">Nama Kategori</label>
                                                        <input type="text" id="nama-kategori-{{ $k->id }}" name="nama" class="form-control" value="{{ $k->nama }}" maxlength="255" required>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="bi bi-info-circle me-2"></i>
                                    @if($search)
                                        Tidak ada kategori dengan nama "{{ $search }}".
                                    @else
                                        Belum ada data kategori.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($kategoris->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $kategoris->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</x-bootstrap-layout>
