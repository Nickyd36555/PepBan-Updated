import SwiftUI

struct ClientDetailView: View {
    let clientId: Int
    @State private var response: ClientDetailResponse?
    @State private var isLoading = true
    @State private var error: String?
    @State private var actionError: String?
    @State private var isActioning = false

    var client: ClientDetail? { response?.client }

    var body: some View {
        Group {
            if isLoading {
                ProgressView("Loading…")
            } else if let err = error {
                ContentUnavailableView("Error", systemImage: "exclamationmark.triangle", description: Text(err))
            } else if let c = client, let r = response {
                List {
                    Section("Contact") {
                        LabeledContent("Name", value: c.ownerName)
                        LabeledContent("Email", value: c.ownerEmail)
                        LabeledContent("Site", value: c.siteUrl)
                    }

                    Section("Account") {
                        HStack {
                            Text("Status")
                            Spacer()
                            Text(c.subscriptionStatus.capitalized)
                                .foregroundStyle(statusColor(c.subscriptionStatus))
                                .fontWeight(.medium)
                        }
                        LabeledContent("Reports", value: "\(r.reportCount)")
                        LabeledContent("Whitelisted", value: "\(r.whitelistCount) customers")
                        LabeledContent("Joined", value: shortDate(c.dateRegistered))
                        if let act = c.activatedAt {
                            LabeledContent("Activated", value: shortDate(act))
                        }
                        if let last = c.lastActive {
                            LabeledContent("Last active", value: shortDate(last))
                        }
                    }

                    Section("Actions") {
                        if let err = actionError {
                            Text(err).foregroundStyle(.red).font(.footnote)
                        }
                        if c.subscriptionStatus != "active" {
                            Button(isActioning ? "Activating…" : "Activate Client") {
                                performAction { try await APIClient.shared.activateClient(id: clientId) }
                            }
                            .foregroundStyle(.green)
                            .disabled(isActioning)
                        } else {
                            Button(isActioning ? "Deactivating…" : "Deactivate Client") {
                                performAction { try await APIClient.shared.deactivateClient(id: clientId) }
                            }
                            .foregroundStyle(.orange)
                            .disabled(isActioning)
                        }
                    }

                    if !r.recentReports.isEmpty {
                        Section("Recent Reports") {
                            ForEach(Array(r.recentReports.enumerated()), id: \.offset) { _, rep in
                                VStack(alignment: .leading, spacing: 2) {
                                    let name = "\(rep.firstName) \(rep.lastName)".trimmingCharacters(in: .whitespaces)
                                    Text(name.isEmpty ? rep.email : "\(name) (\(rep.email))")
                                        .font(.footnote)
                                    if !rep.reason.isEmpty {
                                        Text(rep.reason)
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
                                    Text(shortDate(rep.dateReported))
                                        .font(.caption2)
                                        .foregroundStyle(.tertiary)
                                }
                                .padding(.vertical, 2)
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle("Client Detail")
        .navigationBarTitleDisplayMode(.inline)
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        do {
            response = try await APIClient.shared.clientDetail(id: clientId)
        } catch {
            self.error = error.localizedDescription
        }
        isLoading = false
    }

    private func performAction(_ action: @escaping () async throws -> Void) {
        isActioning = true
        actionError = nil
        Task {
            do {
                try await action()
                await load()
            } catch {
                actionError = error.localizedDescription
            }
            isActioning = false
        }
    }

    private func statusColor(_ s: String) -> Color {
        switch s {
        case "active": return .green
        case "pending": return .orange
        case "suspended": return .red
        default: return .gray
        }
    }

    private func shortDate(_ str: String) -> String {
        let f = DateFormatter()
        f.dateFormat = "yyyy-MM-dd HH:mm:ss"
        guard let d = f.date(from: str) else { return str }
        let out = DateFormatter()
        out.dateStyle = .medium
        out.timeStyle = .none
        return out.string(from: d)
    }
}
