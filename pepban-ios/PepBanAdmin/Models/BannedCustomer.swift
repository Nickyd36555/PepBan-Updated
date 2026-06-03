import Foundation

struct BannedCustomer: Decodable, Identifiable {
    let id: Int
    let email: String
    let firstName: String
    let lastName: String
    let phone: String
    let reason: String
    let status: String
    let reportsCount: Int
    let storeCount: Int
    let dateAdded: String
    let flaggedForReview: Int

    var displayName: String {
        let n = "\(firstName) \(lastName)".trimmingCharacters(in: .whitespaces)
        return n.isEmpty ? email : n
    }
}

struct BannedListResponse: Decodable {
    let customers: [BannedCustomer]
    let total: Int
    let page: Int
    let pages: Int
}

struct BannedDetail: Decodable {
    let id: Int
    let email: String
    let firstName: String
    let lastName: String
    let phone: String
    let billingAddress: String
    let ipAddress: String
    let reason: String
    let status: String
    let reportsCount: Int
    let storeCount: Int
    let dateAdded: String
    let lastUpdated: String
    let adminNotes: String
    let flaggedForReview: Int
}

struct BanReport: Decodable, Identifiable {
    let id: Int
    let siteUrl: String?
    let ownerName: String?
    let reason: String
    let orderId: String?
    let dateReported: String
}

struct BannedDetailResponse: Decodable {
    let customer: BannedDetail
    let reports: [BanReport]
}

struct NewBan: Encodable {
    var email: String
    var firstName: String = ""
    var lastName: String = ""
    var phone: String = ""
    var reason: String = ""
}
