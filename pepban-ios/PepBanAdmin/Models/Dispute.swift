import Foundation

struct Dispute: Decodable, Identifiable {
    let id: Int
    let email: String
    let name: String
    let storeHint: String
    let reason: String
    let status: String
    let adminNotes: String
    let dateAdded: String
}

struct DisputesListResponse: Decodable {
    let disputes: [Dispute]
    let total: Int
    let page: Int
    let pages: Int
}
