import Foundation

struct AuditEntry: Decodable, Identifiable {
    let id: Int
    let actor: String
    let action: String
    let targetType: String
    let targetId: Int
    let details: String
    let createdAt: String
}

struct AuditListResponse: Decodable {
    let entries: [AuditEntry]
    let total: Int
    let page: Int
    let pages: Int
}
