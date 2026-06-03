import SwiftUI

struct BannedListView: View {
    @State private var customers: [BannedCustomer] = []
    @State private var search = ""
    @State private var statusFilter = "active"
    @State private var page = 1
    @State private var totalPages = 1
    @State private var isLoading = false
    @State private var error: String?
    @State private var showAddSheet = false

    private let statusOptions = ["active", "inactive", "pending", ""]

    var body: some View {
        NavigationStack {
            Group {
                if isLoading && customers.isEmpty {
                    ProgressView("Loading…")
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                } else if let err = error, customers.isEmpty {
                    ContentUnavailableView("Error", systemImage: "exclamationmark.triangle", description: Text(err))
                } else if customers.isEmpty {
                    ContentUnavailableView("No customers", systemImage: "person.slash")
                } else {
                    List {
                        ForEach(customers) { c in
                            NavigationLink(destination: BannedDetailView(customerId: c.id)) {
                                BannedRow(customer: c)
                            }
                        }
                        if page < totalPages {
                            Button("Load more") { loadMore() }
                                .frame(maxWidth: .infinity)
                                .foregroundStyle(.red)
                        }
                    }
                }
            }
            .navigationTitle("Banned Customers")
            .searchable(text: $search, prompt: "Email, name, phone…")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button(action: { showAddSheet = true }) {
                        Image(systemName: "plus")
                    }
                }
                ToolbarItem(placement: .topBarLeading) {
                    Menu {
                        ForEach(statusOptions, id: \.self) { s in
                            Button(s.isEmpty ? "All" : s.capitalized) {
                                statusFilter = s
                            }
                        }
                    } label: {
                        Label(statusFilter.isEmpty ? "All" : statusFilter.capitalized, systemImage: "line.3.horizontal.decrease.circle")
                    }
                }
            }
            .sheet(isPresented: $showAddSheet) {
                AddBanView(onAdded: { reset() })
            }
            .onChange(of: search) { _, _ in
                Task {
                    try? await Task.sleep(nanoseconds: 400_000_000)
                    reset()
                }
            }
            .onChange(of: statusFilter) { _, _ in reset() }
            .task { await load() }
        }
    }

    private func reset() {
        page = 1
        customers = []
        Task { await load() }
    }

    private func loadMore() {
        page += 1
        Task { await load() }
    }

    private func load() async {
        isLoading = true
        error = nil
        do {
            let result = try await APIClient.shared.bannedList(page: page, search: search, status: statusFilter)
            if page == 1 { customers = result.customers }
            else { customers.append(contentsOf: result.customers) }
            totalPages = result.pages
        } catch {
            self.error = error.localizedDescription
        }
        isLoading = false
    }
}

struct BannedRow: View {
    let customer: BannedCustomer

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                Text(customer.displayName)
                    .fontWeight(.medium)
                Spacer()
                StatusBadge(status: customer.status)
                if customer.flaggedForReview == 1 {
                    Image(systemName: "flag.fill").foregroundStyle(.orange).font(.caption)
                }
            }
            Text(customer.email)
                .font(.footnote)
                .foregroundStyle(.secondary)
            HStack(spacing: 12) {
                Label("\(customer.reportsCount) reports", systemImage: "doc.text")
                Label("\(customer.storeCount) stores", systemImage: "building.2")
            }
            .font(.caption)
            .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
    }
}

struct StatusBadge: View {
    let status: String

    var color: Color {
        switch status {
        case "active":   return .red
        case "inactive": return .gray
        case "pending":  return .orange
        default:         return .secondary
        }
    }

    var body: some View {
        Text(status.capitalized)
            .font(.caption2.bold())
            .padding(.horizontal, 6)
            .padding(.vertical, 2)
            .background(color.opacity(0.15))
            .foregroundStyle(color)
            .clipShape(Capsule())
    }
}
