import SwiftUI

struct DisputesView: View {
    @State private var disputes: [Dispute] = []
    @State private var statusFilter = "open"
    @State private var page = 1
    @State private var totalPages = 1
    @State private var isLoading = false
    @State private var error: String?
    @State private var actionError: String?

    private let statusOptions = ["open", "resolved", "dismissed", ""]

    var body: some View {
        NavigationStack {
            Group {
                if isLoading && disputes.isEmpty {
                    ProgressView("Loading…")
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                } else if let err = error, disputes.isEmpty {
                    ContentUnavailableView("Error", systemImage: "exclamationmark.triangle", description: Text(err))
                } else if disputes.isEmpty {
                    ContentUnavailableView("No disputes", systemImage: "checkmark.bubble")
                } else {
                    List {
                        if let err = actionError {
                            Section {
                                Text(err).foregroundStyle(.red).font(.footnote)
                            }
                        }
                        ForEach(disputes) { d in
                            DisputeRow(dispute: d) { action in
                                await performAction(disputeId: d.id, action: action)
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
            .navigationTitle("Disputes")
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
            .onChange(of: statusFilter) { _, _ in reset() }
            .task { await load() }
        }
    }

    private func reset() {
        page = 1
        disputes = []
        Task { await load() }
    }

    private func loadMore() {
        page += 1
        Task { await load() }
    }

    private func load() async {
        isLoading = true
        do {
            let result = try await APIClient.shared.disputesList(page: page, status: statusFilter)
            if page == 1 { disputes = result.disputes }
            else { disputes.append(contentsOf: result.disputes) }
            totalPages = result.pages
        } catch {
            self.error = error.localizedDescription
        }
        isLoading = false
    }

    private func performAction(disputeId: Int, action: String) async {
        actionError = nil
        do {
            switch action {
            case "resolve": try await APIClient.shared.resolveDispute(id: disputeId)
            case "dismiss": try await APIClient.shared.dismissDispute(id: disputeId)
            default: break
            }
            reset()
        } catch {
            actionError = error.localizedDescription
        }
    }
}

struct DisputeRow: View {
    let dispute: Dispute
    let onAction: (String) async -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Text(dispute.email)
                    .fontWeight(.medium)
                    .font(.footnote)
                Spacer()
                StatusBadge(status: dispute.status)
            }
            if !dispute.name.isEmpty {
                Text(dispute.name)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            Text(dispute.reason)
                .font(.caption)
                .foregroundStyle(.secondary)
                .lineLimit(2)

            if dispute.status == "open" {
                HStack(spacing: 12) {
                    Button("Resolve") {
                        Task { await onAction("resolve") }
                    }
                    .font(.caption.bold())
                    .foregroundStyle(.green)

                    Button("Dismiss") {
                        Task { await onAction("dismiss") }
                    }
                    .font(.caption.bold())
                    .foregroundStyle(.orange)
                }
                .padding(.top, 2)
            }
        }
        .padding(.vertical, 4)
    }
}
