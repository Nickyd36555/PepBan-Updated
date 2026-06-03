import SwiftUI

struct ClientsListView: View {
    @State private var clients: [Client] = []
    @State private var search = ""
    @State private var statusFilter = ""
    @State private var page = 1
    @State private var totalPages = 1
    @State private var isLoading = false
    @State private var error: String?

    private let statusOptions = ["", "active", "pending", "inactive", "suspended"]

    var body: some View {
        NavigationStack {
            Group {
                if isLoading && clients.isEmpty {
                    ProgressView("Loading…")
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                } else if let err = error, clients.isEmpty {
                    ContentUnavailableView("Error", systemImage: "exclamationmark.triangle", description: Text(err))
                } else if clients.isEmpty {
                    ContentUnavailableView("No clients", systemImage: "building.2")
                } else {
                    List {
                        ForEach(clients) { c in
                            NavigationLink(destination: ClientDetailView(clientId: c.id)) {
                                ClientRow(client: c)
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
            .navigationTitle("Clients")
            .searchable(text: $search, prompt: "Name, email, URL…")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Menu {
                        ForEach(statusOptions, id: \.self) { s in
                            Button(s.isEmpty ? "All" : s.capitalized) { statusFilter = s }
                        }
                    } label: {
                        Label(statusFilter.isEmpty ? "All" : statusFilter.capitalized, systemImage: "line.3.horizontal.decrease.circle")
                    }
                }
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
        clients = []
        Task { await load() }
    }

    private func loadMore() {
        page += 1
        Task { await load() }
    }

    private func load() async {
        isLoading = true
        do {
            let result = try await APIClient.shared.clientsList(page: page, search: search, status: statusFilter)
            if page == 1 { clients = result.clients }
            else { clients.append(contentsOf: result.clients) }
            totalPages = result.pages
        } catch {
            self.error = error.localizedDescription
        }
        isLoading = false
    }
}

struct ClientRow: View {
    let client: Client

    var statusColor: Color {
        switch client.subscriptionStatus {
        case "active":    return .green
        case "pending":   return .orange
        case "suspended": return .red
        default:          return .gray
        }
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                Text(client.ownerName.isEmpty ? client.ownerEmail : client.ownerName)
                    .fontWeight(.medium)
                Spacer()
                Circle()
                    .fill(statusColor)
                    .frame(width: 8, height: 8)
            }
            Text(client.siteUrl)
                .font(.footnote)
                .foregroundStyle(.secondary)
            Text(client.subscriptionStatus.capitalized)
                .font(.caption)
                .foregroundStyle(statusColor)
        }
        .padding(.vertical, 2)
    }
}
